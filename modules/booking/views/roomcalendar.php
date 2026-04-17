<?php
/**
 * @filesource modules/booking/views/roomcalendar.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Booking\Roomcalendar;

use Kotchasan\Html;

/**
 * module=booking-roomcalendar
 * แสดงปฏิทินการจองรายห้อง (Hotel-style)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงปฏิทินการจองของห้อง
     *
     * @param object $room ข้อมูลห้อง
     * @param array  $login
     *
     * @return string
     */
    public function render($room, $login)
    {
        $section = Html::create('div', [
            'class' => 'room-calendar-page'
        ]);
        // ข้อมูลห้อง
        $room_info = $section->add('div', [
            'class' => 'room-calendar-info'
        ]);
        // รูปภาพห้อง (รองรับหลายรูป)
        $images = \Booking\Write\Model::getRoomImages($room->id);
        if (!empty($images)) {
            if (count($images) === 1) {
                $singleImgHtml = '<img src="'.$images[0]['url'].'" alt="'.htmlspecialchars($room->name).'" style="cursor:pointer" onclick="roomGalleryOpenSingle(\''.$images[0]['url'].'\')">'; 
                $room_info->add('div', [
                    'class' => 'room-calendar-img',
                    'innerHTML' => $singleImgHtml
                ]);
            } else {
                $galleryHtml = '<div class="room-gallery room-gallery-inline" id="room_gallery_cal_'.$room->id.'">';
                $galleryHtml .= '<div class="room-gallery-main">';
                $galleryHtml .= '<button type="button" class="room-gallery-prev" onclick="roomGalleryNav(\'cal_'.$room->id.'\', -1)">&#10094;</button>';
                $galleryHtml .= '<img id="room_gallery_img_cal_'.$room->id.'" src="'.$images[0]['url'].'" alt="'.htmlspecialchars($room->name).'" style="cursor:pointer" onclick="roomGalleryOpenLightbox(\'cal_'.$room->id.'\')">';
                $galleryHtml .= '<button type="button" class="room-gallery-next" onclick="roomGalleryNav(\'cal_'.$room->id.'\', 1)">&#10095;</button>';
                $galleryHtml .= '<div class="room-gallery-counter"><span id="room_gallery_num_cal_'.$room->id.'">1</span> / '.count($images).'</div>';
                $galleryHtml .= '</div>';
                $galleryHtml .= '<div class="room-gallery-thumbs">';
                foreach ($images as $idx => $img) {
                    $activeClass = $idx === 0 ? ' active' : '';
                    $galleryHtml .= '<img class="room-gallery-thumb'.$activeClass.'" src="'.$img['url'].'" onclick="roomGalleryGoto(\'cal_'.$room->id.'\', '.$idx.')" alt="thumb '.($idx+1).'">';
                }
                $galleryHtml .= '</div>';
                $galleryHtml .= '<script>window.roomGalleryData = window.roomGalleryData || {};window.roomGalleryData["cal_'.$room->id.'"] = {current:0,images:[';
                foreach ($images as $idx => $img) {
                    $galleryHtml .= ($idx > 0 ? ',' : '').'"'.$img['url'].'"';
                }
                $galleryHtml .= ']};</script>';
                $galleryHtml .= '</div>';
                $room_info->add('div', [
                    'class' => 'room-calendar-img room-calendar-gallery',
                    'innerHTML' => $galleryHtml
                ]);
            }
        } else {
            $room_info->add('div', [
                'class' => 'room-calendar-img',
                'innerHTML' => '<img src="'.WEB_URL.'modules/booking/img/noimage.png" alt="'.htmlspecialchars($room->name).'">'
            ]);
        }
        // รายละเอียด
        $room_detail = $room_info->add('div', [
            'class' => 'room-calendar-detail'
        ]);
        $room_detail->add('div', [
            'innerHTML' => '<h3><span class="room-color-dot" style="background-color:'.$room->color.'"></span>'.htmlspecialchars($room->name).'</h3>'
        ]);
        if (!empty($room->detail)) {
            $room_detail->add('p', [
                'innerHTML' => nl2br(htmlspecialchars($room->detail))
            ]);
        }
        $room_detail->add('div', [
            'class' => 'room-calendar-hint',
            'innerHTML' => '<span class="icon-info"></span> {LNG_Click on date to book this room}'
        ]);
        // Legend
        $room_detail->add('div', [
            'class' => 'room-calendar-legend',
            'innerHTML' => '<div><span class="room-color-dot" style="background-color:#FF0000"></span> <span>{LNG_สีแดง คือ จองแล้ว อยู่ระหว่างรอตรวจสอบยืนยัน}</span></div>' .
                           '<div><span class="room-color-dot" style="background-color:'.$room->color.'"></span> <span>{LNG_สีตามห้องประชุม คือ การจองได้รับการอนุมัติแล้ว}</span></div>'
        ]);
        // ปฏิทิน
        $section->add('div', [
            'id' => 'booking-calendar'
        ]);
        // hidden room_id
        $section->add('input', [
            'type' => 'hidden',
            'id' => 'calendar_room_id',
            'value' => $room->id
        ]);
        // คืนค่าปีที่มีการจองสูงสุดและต่ำสุด
        $range = \Booking\Home\Model::getYearRange();
        /* Javascript */
        $section->script('initRoomCalendar('.$range->min.', '.$range->max.', '.$room->id.');');
        // คืนค่า HTML
        return $section->render();
    }
}
