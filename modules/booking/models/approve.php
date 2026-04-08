<?php
/**
 * @filesource modules/booking/models/approve.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Booking\Approve;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=booking-approve
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        $query = static::createQuery()
            ->from('reservation V')
            ->join('user U', 'LEFT', ['U.id', 'V.member_id'])
            ->where(['V.id', $id]);
        $select = ['V.*', 'U.name', 'U.phone', 'U.username'];
        $n = 1;
        foreach (Language::get('BOOKING_SELECT', []) + Language::get('BOOKING_OPTIONS', []) + Language::get('BOOKING_RADIO', []) + Language::get('BOOKING_TEXT', []) as $key => $label) {
            $query->join('reservation_data M'.$n, 'LEFT', [['M'.$n.'.reservation_id', 'V.id'], ['M'.$n.'.name', $key]]);
            $select[] = 'M'.$n.'.value '.$key;
            ++$n;
        }
        $query->join('reservation_data M'.$n, 'LEFT', [['M'.$n.'.reservation_id', 'V.id'], ['M'.$n.'.name', 'attachment']]);
        $select[] = 'M'.$n.'.value attachment';
        return $query->first($select);
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (approve.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, สามารถอนุมัติได้
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_approve_room')) {
                try {
                    // ค่าที่ส่งมา
                    $save = [
                        'room_id' => $request->post('room_id')->toInt(),
                        'attendees' => $request->post('attendees')->toInt(),
                        'topic' => $request->post('topic')->topic(),
                        'comment' => $request->post('comment')->textarea(),
                        'status' => $request->post('status')->toInt(),
                        'reason' => $request->post('reason')->topic()
                    ];
                    $begin_date = $request->post('begin_date')->date();
                    $begin_time = $request->post('begin_time')->time();
                    $end_date = $request->post('end_date')->date();
                    $end_time = $request->post('end_time')->time();
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt());
                    if ($index) {
                        if ($save['topic'] == '') {
                            // ไม่ได้กรอก topic
                            $ret['ret_topic'] = 'Please fill in';
                        }
                        if ($save['attendees'] == 0) {
                            // ไม่ได้กรอก attendees
                            $ret['ret_attendees'] = 'Please fill in';
                        }
                        if (empty($begin_date)) {
                            // ไม่ได้กรอก begin_date
                            $ret['ret_begin_date'] = 'Please fill in';
                        }
                        if (empty($begin_time)) {
                            // ไม่ได้กรอก begin_time
                            $ret['ret_begin_time'] = 'Please fill in';
                        }
                        if (empty($end_date)) {
                            // ไม่ได้กรอก end
                            $ret['ret_end_date'] = 'Please fill in';
                        }
                        if (empty($end_time)) {
                            // ไม่ได้กรอก end_time
                            $ret['ret_end_time'] = 'Please fill in';
                        }
                        if ($end_date.$end_time > $begin_date.$begin_time) {
                            $save['begin'] = $begin_date.' '.$begin_time.':01';
                            $save['end'] = $end_date.' '.$end_time.':00';
                        } else {
                            // วันที่ ไม่ถูกต้อง
                            $ret['ret_end_date'] = Language::get('End date must be greater than begin date');
                        }
                        $datas = [];
                        foreach (Language::get('BOOKING_SELECT', []) as $key => $label) {
                            $value = $request->post($key)->toInt();
                            if ($value > 0) {
                                $datas[$key] = $value;
                            }
                        }
                        foreach (Language::get('BOOKING_RADIO', []) as $key => $label) {
                            $value = $request->post($key)->topic();
                            if ($value != '') {
                                $datas[$key] = $value;
                            }
                        }
                        foreach (Language::get('BOOKING_TEXT', []) as $key => $label) {
                            $value = $request->post($key)->topic();
                            if ($value != '') {
                                $datas[$key] = $value;
                            }
                        }
                        foreach (Language::get('BOOKING_OPTIONS', []) as $key => $label) {
                            $values = $request->post($key, [])->toInt();
                            if (!empty($values)) {
                                $datas[$key] = implode(',', $values);
                            }
                        }
                        if (empty($ret)) {
                            // อัปโหลดไฟล์แนบ
                            foreach ($request->getUploadedFiles() as $item => $file) {
                                /* @var $file \Kotchasan\Http\UploadedFile */
                                if ($item === 'attachment' && $file->hasUploadFile()) {
                                    $dir = ROOT_PATH.DATA_FOLDER.'booking/';
                                    if (!\Kotchasan\File::makeDirectory($dir)) {
                                        $ret['ret_'.$item] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'booking/');
                                    } elseif (!$file->validFileExt(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpeg', 'jpg', 'png', 'zip', 'rar'])) {
                                        $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                    } else {
                                        $ext = $file->getClientFileExt();
                                        $fileName = 'attachment_'.$index->id.'.'.$ext;
                                        try {
                                            $file->moveTo($dir.$fileName);
                                            $datas['attachment'] = $fileName;
                                        } catch (\Exception $exc) {
                                            $ret['ret_'.$item] = Language::get($exc->getMessage());
                                        }
                                    }
                                }
                            }
                        }
                        if (empty($ret)) {
                            // ตาราง
                            $reservation_table = $this->getTableName('reservation');
                            $reservation_data = $this->getTableName('reservation_data');
                            // Database
                            $db = $this->db();
                            // save
                            $db->update($reservation_table, $index->id, $save);
                            // รายละเอียดการจอง
                            if (empty($datas['attachment']) && !empty($index->attachment)) {
                                $datas['attachment'] = $index->attachment;
                            }
                            $db->delete($reservation_data, ['reservation_id', $index->id], 0);
                            foreach ($datas as $key => $value) {
                                if ($value != '') {
                                    $db->insert($reservation_data, [
                                        'reservation_id' => $index->id,
                                        'name' => $key,
                                        'value' => $value
                                    ]);
                                }
                                $save[$key] = $value;
                            }
                            if ($request->post('send_mail')->toBoolean()) {
                                // ส่งอีเมลไปยังผู้ที่เกี่ยวข้อง
                                $save['id'] = $index->id;
                                $save['member_id'] = $index->member_id;
                                $save['create_date'] = $index->create_date;
                                $ret['alert'] = \Booking\Email\Model::send($save);
                            } else {
                                // ไม่ส่งอีเมล
                                $ret['alert'] = Language::get('Saved successfully');
                            }
                            // log
                            \Index\Log\Model::add($index->id, 'booking', 'Status', Language::get('BOOKING_STATUS', '', $save['status']), $login['id']);
                            // location
                            $ret['location'] = $request->getUri()->postBack('index.php', ['module' => 'booking-report', 'status' => $index->status]);
                            // เคลียร์
                            $request->removeToken();

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
}
