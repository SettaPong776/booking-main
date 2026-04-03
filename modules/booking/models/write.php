<?php
/**
 * @filesource modules/booking/models/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Booking\Write;

use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=booking-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int  $id     ID
     *
     * @return object|null
     */
    public static function get($id)
    {
        if (empty($id)) {
            // ใหม่
            return (object) [
                'id' => 0
            ];
        } else {
            // แก้ไข อ่านรายการที่เลือก
            $query = static::createQuery()
                ->from('rooms R')
                ->where(['R.id', $id]);
            $select = ['R.*'];
            $n = 1;
            foreach (Language::get('ROOM_CUSTOM_TEXT', []) as $key => $label) {
                $query->join('rooms_meta M'.$n, 'LEFT', [['M'.$n.'.room_id', 'R.id'], ['M'.$n.'.name', $key]]);
                $select[] = 'M'.$n.'.value '.$key;
                ++$n;
            }
            return $query->first($select);
        }
    }

    /**
     * คืนค่ารายการรูปภาพทั้งหมดของห้อง
     *
     * @param int $room_id
     * @return array
     */
    public static function getRoomImages($room_id)
    {
        $images = [];
        $dir = ROOT_PATH.DATA_FOLDER.'booking/';
        $imgType = self::$cfg->stored_img_type;
        $maxImages = 10;

        // ภาพหลัก
        if (is_file($dir.$room_id.$imgType)) {
            $images[] = [
                'file' => $dir.$room_id.$imgType,
                'url' => WEB_URL.DATA_FOLDER.'booking/'.$room_id.$imgType,
                'key' => 1
            ];
        }
        // ภาพเพิ่มเติม 2-10
        for ($i = 2; $i <= $maxImages; $i++) {
            if (is_file($dir.$room_id.'-'.$i.$imgType)) {
                $images[] = [
                    'file' => $dir.$room_id.'-'.$i.$imgType,
                    'url' => WEB_URL.DATA_FOLDER.'booking/'.$room_id.'-'.$i.$imgType,
                    'key' => $i
                ];
            }
        }
        return $images;
    }

    /**
     * ลบรูปภาพของห้อง
     *
     * @param int $room_id
     * @param int $img_key (1 = main, 2-10 = additional)
     */
    public static function deleteRoomImage($room_id, $img_key)
    {
        $dir = ROOT_PATH.DATA_FOLDER.'booking/';
        $imgType = self::$cfg->stored_img_type;
        if ($img_key == 1) {
            $file = $dir.$room_id.$imgType;
        } else {
            $file = $dir.$room_id.'-'.$img_key.$imgType;
        }
        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * ลบรูปภาพทั้งหมดของห้อง
     *
     * @param int $room_id
     */
    public static function deleteAllRoomImages($room_id)
    {
        $dir = ROOT_PATH.DATA_FOLDER.'booking/';
        $imgType = self::$cfg->stored_img_type;
        // ลบภาพหลัก
        $file = $dir.$room_id.$imgType;
        if (is_file($file)) {
            unlink($file);
        }
        // ลบภาพเพิ่มเติม
        for ($i = 2; $i <= 10; $i++) {
            $file = $dir.$room_id.'-'.$i.$imgType;
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (write.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, can_manage_room, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_room')) {
                try {
                    // ค่าที่ส่งมา
                    $save = [
                        'name' => $request->post('name')->topic(),
                        'color' => $request->post('color')->filter('\#A-Z0-9'),
                        'detail' => $request->post('detail')->textarea()
                    ];
                    $metas = [];
                    foreach (Language::get('ROOM_CUSTOM_TEXT', []) as $key => $label) {
                        $metas[$key] = $request->post($key)->topic();
                    }
                    $id = $request->post('id')->toInt();
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($id);
                    if ($index) {
                        if ($save['name'] == '') {
                            // ไม่ได้กรอก name
                            $ret['ret_name'] = 'Please fill in';
                        } else {
                            // Database
                            $db = $this->db();
                            // table
                            $table = $this->getTableName('rooms');
                            if ($index->id == 0) {
                                $save['id'] = $db->getNextId($table);
                            } else {
                                $save['id'] = $index->id;
                            }
                            // ไดเร็คทอรี่เก็บไฟล์
                            $dir = ROOT_PATH.DATA_FOLDER.'booking/';
                            $imgType = self::$cfg->stored_img_type;
                            $maxImages = 10;

                            // นับจำนวนรูปที่มีอยู่แล้ว
                            $existingCount = count(self::getRoomImages($save['id']));

                            // อัปโหลดไฟล์หลายรูป
                            foreach ($request->getUploadedFiles() as $item => $file) {
                                /* @var $file \Kotchasan\Http\UploadedFile */
                                if ($file->hasUploadFile()) {
                                    if (!File::makeDirectory($dir)) {
                                        // ไดเรคทอรี่ไม่สามารถสร้างได้
                                        $ret['ret_'.$item] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'booking/');
                                    } elseif (!$file->validFileExt(self::$cfg->booking_img_typies)) {
                                        // ชนิดของไฟล์ไม่ถูกต้อง
                                        $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                    } else {
                                        // ตรวจสอบจำนวนไม่เกิน maxImages
                                        if ($existingCount >= $maxImages) {
                                            $ret['ret_'.$item] = Language::get('Cannot upload more than').' '.$maxImages.' '.Language::get('Image');
                                            break;
                                        }
                                        // หาช่องว่างถัดไปสำหรับเก็บรูป
                                        $imgName = $this->getNextImageSlot($save['id'], $dir, $imgType, $maxImages);
                                        if ($imgName === false) {
                                            $ret['ret_'.$item] = Language::get('Cannot upload more than').' '.$maxImages.' '.Language::get('Image');
                                            break;
                                        }
                                        try {
                                            $file->resizeImage(self::$cfg->booking_img_typies, $dir, $imgName, self::$cfg->booking_w);
                                            $existingCount++;
                                        } catch (\Exception $exc) {
                                            // ไม่สามารถอัปโหลดได้
                                            $ret['ret_'.$item] = Language::get($exc->getMessage());
                                        }
                                    }
                                } elseif ($file->hasError()) {
                                    // ข้อผิดพลาดการอัปโหลด
                                    $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                                }
                            }
                            if (empty($ret)) {
                                if ($index->id == 0) {
                                    // ใหม่
                                    $db->insert($table, $save);
                                } else {
                                    // แก้ไข
                                    $db->update($table, $save['id'], $save);
                                }
                                // อัปเดต meta
                                $meta_table = $this->getTableName('rooms_meta');
                                $db->delete($meta_table, ['room_id', $save['id']], 0);
                                foreach ($metas as $key => $value) {
                                    if ($value != '') {
                                        $db->insert($meta_table, [
                                            'room_id' => $save['id'],
                                            'name' => $key,
                                            'value' => $value
                                        ]);
                                    }
                                }
                                // log
                                \Index\Log\Model::add($save['id'], 'booking', 'Save', '{LNG_Room} ID : '.$save['id'], $login['id']);
                                // คืนค่า
                                $ret['alert'] = Language::get('Saved successfully');
                                $ret['location'] = $request->getUri()->postBack('index.php', ['module' => 'booking-setup']);
                                // เคลียร์
                                $request->removeToken();
                            }
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * ลบรูปภาพห้อง (AJAX action)
     *
     * @param Request $request
     */
    public function deleteImage(Request $request)
    {
        $ret = [];
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_room')) {
                $room_id = $request->post('room_id')->toInt();
                $img_key = $request->post('img_key')->toInt();
                if ($room_id > 0 && $img_key > 0 && $img_key <= 10) {
                    self::deleteRoomImage($room_id, $img_key);
                    // Re-sequence images to fill gaps
                    self::resequenceImages($room_id);
                    $ret['alert'] = Language::get('Saved successfully');
                    $ret['location'] = 'reload';
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        echo json_encode($ret);
    }

    /**
     * จัดเรียงลำดับรูปภาพใหม่ หลังจากลบ เพื่อไม่ให้มีช่องว่าง
     *
     * @param int $room_id
     */
    private static function resequenceImages($room_id)
    {
        $dir = ROOT_PATH.DATA_FOLDER.'booking/';
        $imgType = self::$cfg->stored_img_type;
        $maxImages = 10;

        // รวบรวมไฟล์ที่มีอยู่
        $existingFiles = [];
        if (is_file($dir.$room_id.$imgType)) {
            $existingFiles[] = $dir.$room_id.$imgType;
        }
        for ($i = 2; $i <= $maxImages; $i++) {
            $file = $dir.$room_id.'-'.$i.$imgType;
            if (is_file($file)) {
                $existingFiles[] = $file;
            }
        }

        // ลบไฟล์ทั้งหมดก่อน (rename ทับไม่ได้ถ้าชื่อซ้ำ)
        // ย้ายไปเป็นชื่อชั่วคราวก่อน
        $tempFiles = [];
        foreach ($existingFiles as $idx => $file) {
            $tempName = $dir.$room_id.'_temp_'.$idx.$imgType;
            rename($file, $tempName);
            $tempFiles[] = $tempName;
        }

        // ย้ายกลับด้วยชื่อที่ถูกต้อง
        foreach ($tempFiles as $idx => $tempFile) {
            if ($idx === 0) {
                $newName = $dir.$room_id.$imgType;
            } else {
                $newName = $dir.$room_id.'-'.($idx + 1).$imgType;
            }
            rename($tempFile, $newName);
        }
    }

    /**
     * หาชื่อไฟล์ถัดไปที่ว่างสำหรับเก็บรูปภาพ
     *
     * @param int $room_id
     * @param string $dir
     * @param string $imgType
     * @param int $maxImages
     * @return string|false ชื่อไฟล์ หรือ false ถ้าเต็มแล้ว
     */
    private function getNextImageSlot($room_id, $dir, $imgType, $maxImages)
    {
        // ตรวจสอบช่องหลัก
        if (!is_file($dir.$room_id.$imgType)) {
            return $room_id.$imgType;
        }
        // ตรวจสอบช่อง 2-10
        for ($i = 2; $i <= $maxImages; $i++) {
            if (!is_file($dir.$room_id.'-'.$i.$imgType)) {
                return $room_id.'-'.$i.$imgType;
            }
        }
        return false;
    }
}
