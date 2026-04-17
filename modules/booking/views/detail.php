<?php
/**
 * @filesource modules/booking/views/detail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Booking\Detail;

use Kotchasan\Language;

/**
 * module=booking-rooms
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรายละเอียดห้อง
     *
     * @param object $order
     *
     * @return string
     */
    public function room($order)
    {
        $content = '<article class="modal_detail">';
        $content .= '<header><h3 class="icon-office cuttext">{LNG_Details of} {LNG_Room}</h3></header>';

        // แสดงรูปภาพทั้งหมดของห้อง (gallery)
        $images = \Booking\Write\Model::getRoomImages($order->id);
        if (!empty($images)) {
            if (count($images) === 1) {
                // แสดงรูปเดียว
                $content .= '<figure class="center"><img src="'.$images[0]['url'].'" alt="'.htmlspecialchars($order->name).'" style="cursor:pointer" onclick="roomGalleryOpenSingle(\''.$images[0]['url'].'\')">' . '</figure>';
            } else {
                // แสดง gallery slider
                $content .= '<div class="room-gallery" id="room_gallery_'.$order->id.'">';
                $content .= '<div class="room-gallery-main">';
                $content .= '<button type="button" class="room-gallery-prev" onclick="roomGalleryNav('.$order->id.', -1)">&#10094;</button>';
                $content .= '<img id="room_gallery_img_'.$order->id.'" src="'.$images[0]['url'].'" alt="'.htmlspecialchars($order->name).'" style="cursor:pointer" onclick="roomGalleryOpenLightbox('.$order->id.')">';
                $content .= '<button type="button" class="room-gallery-next" onclick="roomGalleryNav('.$order->id.', 1)">&#10095;</button>';
                $content .= '<div class="room-gallery-counter"><span id="room_gallery_num_'.$order->id.'">1</span> / '.count($images).'</div>';
                $content .= '</div>';
                $content .= '<div class="room-gallery-thumbs">';
                foreach ($images as $idx => $img) {
                    $activeClass = $idx === 0 ? ' active' : '';
                    $content .= '<img class="room-gallery-thumb'.$activeClass.'" src="'.$img['url'].'" onclick="roomGalleryGoto('.$order->id.', '.$idx.')" alt="thumb '.($idx+1).'">';
                }
                $content .= '</div>';
                // Hidden data for JS
                $content .= '<script>window.roomGalleryData = window.roomGalleryData || {};window.roomGalleryData['.$order->id.'] = {current:0,images:[';
                foreach ($images as $idx => $img) {
                    $content .= ($idx > 0 ? ',' : '').'"'.$img['url'].'"';
                }
                $content .= ']};</script>';
                $content .= '</div>';
            }
        }

        $content .= '<table class="border data fullwidth"><tbody>';
        $content .= '<tr><th>{LNG_Room name}</th><td><span class="term" style="background-color:'.$order->color.'">'.$order->name.'</span></td></tr>';
        if ($order->detail != '') {
            $content .= '<tr><th>{LNG_Detail}</th><td>'.nl2br($order->detail).'</td></tr>';
        }
        foreach (Language::get('ROOM_CUSTOM_TEXT', []) as $key => $label) {
            if (!empty($order->{$key})) {
                $content .= '<tr><th>'.$label.'</th><td>'.$order->{$key}.'</td></tr>';
            }
        }
        $content .= '</tbody></article>';
        $content .= '</article>';
        // คืนค่า HTML
        return Language::trans($content);
    }

    /**
     * แสดงรายละเอียดการจอง
     *
     * @param array $order
     *
     * @return string
     */
    public function booking($order)
    {
        $login = \Kotchasan\Login::isMember();
        $content = '<article class="modal_detail">';
        $content .= '<header><h3 class="icon-calendar cuttext">{LNG_Details of} {LNG_Booking}</h3></header>';
        $content .= '<table class="border data fullwidth"><tbody>';
        $content .= '<tr><th class=top>{LNG_Topic}</th><td>'.$order['topic'].'</td></tr>';
        $content .= '<tr><th>{LNG_Room name}</th><td><span class="term" style="background-color:'.$order['color'].'">'.$order['name'].'</span></td></tr>';
        foreach (Language::get('ROOM_CUSTOM_TEXT', []) as $key => $label) {
            if (!empty($order[$key])) {
                $content .= '<tr><th>'.$label.'</th><td>'.$order[$key].'</td></tr>';
            }
        }
        $content .= '<tr><th>{LNG_Attendees number}</th><td>'.$order['attendees'].'</td></tr>';
        $content .= '<tr><th>{LNG_Contact name}</th><td>'.$order['contact'].'</td></tr>';
        $content .= '<tr><th>{LNG_Phone}</th><td>'.self::showPhone($order['phone'], !$login).'</td></tr>';
        $content .= '<tr><th class=top>{LNG_Booking date}</th><td>'.\Booking\Tools\View::dateRange($order).'</td></tr>';
        // หมวดหมู่
        $category = \Booking\Category\Model::init();
        foreach (Language::get('BOOKING_RADIO', []) as $key => $label) {
            if (!empty($order[$key])) {
                $options = Language::get(strtoupper($key).'_TYPIES', []);
                $val = isset($options[$order[$key]]) ? $options[$order[$key]] : $order[$key];
                $content .= '<tr><th>'.$label.'</th><td>'.$val.'</td></tr>';
            }
        }
        foreach (Language::get('BOOKING_TEXT', []) as $key => $label) {
            if (!empty($order[$key])) {
                $content .= '<tr><th>'.$label.'</th><td>'.$order[$key].'</td></tr>';
            }
        }

        foreach (Language::get('BOOKING_SELECT', []) as $key => $label) {
            if (!empty($order[$key])) {
                $content .= '<tr><th>'.$label.'</th><td>'.$category->get($key, $order[$key]).'</td></tr>';
            }
        }


        if (!empty($order['attachment'])) {
            $url = WEB_URL.DATA_FOLDER.'booking/'.$order['attachment'];
            $content .= '<tr><th>{LNG_Attached file}</th><td><a href="'.$url.'" target="_blank" class="button blue"><span class="icon-download">{LNG_Download}</span></a></td></tr>';
        }

        $content .= '<tr><th>{LNG_Status}</th><td>'.self::showStatus(Language::get('BOOKING_STATUS'), $order['status']).'</td></tr>';
        if (!empty($order['reason'])) {
            $content .= '<tr><th class=top>{LNG_Reason}</th><td>'.$order['reason'].'</td></tr>';
        }
        $content .= '</tbody></article>';
        $content .= '</article>';
        // คืนค่า HTML
        return Language::trans($content);
    }
}
