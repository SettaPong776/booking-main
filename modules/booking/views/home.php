<?php
/**
 * @filesource modules/booking/views/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Booking\Home;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * หน้า Home — แสดงรายการห้องประชุมทั้งหมด (Room Cards)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * หน้า Home
     *
     * @param object $index
     * @param array  $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        $section = Html::create('div');
        $section->add('header', [
            'innerHTML' => '<h2 class="icon-office">{LNG_List of} {LNG_Room}</h2>'
        ]);
        // ดึงข้อมูลห้องทั้งหมด
        $query = \Booking\Rooms\Model::toDataTable()->cacheOn();
        $rooms_html = '';
        foreach ($query->execute() as $item) {
            $thumb = is_file(ROOT_PATH.DATA_FOLDER.'booking/'.$item->id.self::$cfg->stored_img_type)
                ? WEB_URL.DATA_FOLDER.'booking/'.$item->id.self::$cfg->stored_img_type
                : WEB_URL.'modules/booking/img/noimage.png';
            $detail_text = strip_tags($item->detail);
            if (mb_strlen($detail_text) > 120) {
                $detail_text = mb_substr($detail_text, 0, 120).'...';
            }
            $rooms_html .= '<div class="room-card">';
            $rooms_html .= '<div class="room-card-img"><img src="'.$thumb.'" alt="'.htmlspecialchars($item->name).'"></div>';
            $rooms_html .= '<div class="room-card-body">';
            $rooms_html .= '<h3 class="room-card-name"><span class="room-color-dot" style="background-color:'.$item->color.'"></span>'.htmlspecialchars($item->name).'</h3>';
            if ($detail_text != '') {
                $rooms_html .= '<p class="room-card-detail">'.$detail_text.'</p>';
            }
            $rooms_html .= '<div class="room-card-actions">';
            $rooms_html .= '<a class="button icon-calendar blue room-book-btn" href="index.php?module=booking-roomcalendar&amp;room_id='.$item->id.'">{LNG_Book a room}</a>';
            $rooms_html .= '<a class="button icon-info orange room-detail-btn" id="room_detail_'.$item->id.'">{LNG_Detail}</a>';
            $rooms_html .= '</div>';
            $rooms_html .= '</div>';
            $rooms_html .= '</div>';
        }
        $section->add('div', [
            'id' => 'home_room_cards',
            'class' => 'room-cards-grid',
            'innerHTML' => $rooms_html
        ]);
        /* Javascript */
        $section->script('initHomeRoomCards();');
        // คืนค่า HTML
        return $section->render();
    }
}
