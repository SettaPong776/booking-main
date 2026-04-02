<?php
/**
 * @filesource modules/booking/controllers/roomcalendar.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Booking\Roomcalendar;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=booking-roomcalendar
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ปฏิทินการจองรายห้อง
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $room_id = $request->request('room_id')->toInt();
        // อ่านข้อมูลห้อง
        $room = \Booking\Write\Model::get($room_id);
        if ($room) {
            // ข้อความ title bar
            $this->title = Language::trans('{LNG_Booking calendar}').' - '.$room->name;
            // เลือกเมนู
            $this->menu = 'rooms';
            // สมาชิก
            $login = Login::isMember();
            if ($login || empty(self::$cfg->booking_login_type)) {
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('nav', [
                    'class' => 'breadcrumbs'
                ]);
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-calendar">{LNG_Room}</span></li>');
                $ul->appendChild('<li><a href="index.php?module=booking-rooms">{LNG_List of} {LNG_Room}</a></li>');
                $ul->appendChild('<li><span>'.$room->name.'</span></li>');
                $section->add('header', [
                    'innerHTML' => '<h2 class="icon-calendar">{LNG_Booking calendar} — '.$room->name.'</h2>'
                ]);
                $div = $section->add('div', [
                    'class' => 'content_bg'
                ]);
                // แสดงปฏิทิน
                $div->appendChild(\Booking\Roomcalendar\View::create()->render($room, $login));
                // คืนค่า HTML
                return $section->render();
            }
            // ต้องเข้าระบบก่อน
            $url = WEB_URL.'index.php?module=booking-roomcalendar&room_id='.$room_id;
            $ret = '<script>window.location="'.WEB_URL.'index.php?module=welcome&action=login&ret='.urlencode($url).'";</script>';
            return $ret;
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
