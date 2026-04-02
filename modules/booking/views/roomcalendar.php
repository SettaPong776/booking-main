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
        // รูปภาพห้อง
        $thumb = is_file(ROOT_PATH.DATA_FOLDER.'booking/'.$room->id.self::$cfg->stored_img_type)
            ? WEB_URL.DATA_FOLDER.'booking/'.$room->id.self::$cfg->stored_img_type
            : WEB_URL.'modules/booking/img/noimage.png';
        $room_info->add('div', [
            'class' => 'room-calendar-img',
            'innerHTML' => '<img src="'.$thumb.'" alt="'.htmlspecialchars($room->name).'">'
        ]);
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
