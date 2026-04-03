<?php
/**
 * @filesource modules/booking/views/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Booking\Write;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=booking-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มสร้าง/แก้ไข ห้องประชุม
     *
     * @param object $index
     * @param array  $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        $form = Html::create('form', [
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/booking/model/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ]);
        $fieldset = $form->add('fieldset', [
            'titleClass' => 'icon-write',
            'title' => '{LNG_Details of} {LNG_Room}'
        ]);
        // name
        $fieldset->add('text', [
            'id' => 'name',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Room name}',
            'maxlength' => 64,
            'value' => isset($index->name) ? $index->name : ''
        ]);
        // color
        $fieldset->add('color', [
            'id' => 'color',
            'labelClass' => 'g-input icon-color',
            'itemClass' => 'item',
            'label' => '{LNG_Color}',
            'value' => isset($index->color) ? $index->color : ''
        ]);
        // detail
        $fieldset->add('textarea', [
            'id' => 'detail',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Detail}',
            'rows' => 3,
            'value' => isset($index->detail) ? $index->detail : ''
        ]);
        foreach (Language::get('ROOM_CUSTOM_TEXT', []) as $key => $label) {
            $fieldset->add('text', [
                'id' => $key,
                'labelClass' => 'g-input icon-edit',
                'itemClass' => 'item',
                'label' => $label,
                'value' => isset($index->{$key}) ? $index->{$key} : ''
            ]);
        }

        // Multi-image upload section (max 10 images)
        $maxImages = 10;
        $dir = ROOT_PATH.DATA_FOLDER.'booking/';
        $imgType = self::$cfg->stored_img_type;

        // Collect existing images
        $existingImages = [];
        if ($index->id > 0) {
            // Check main image: {id}.jpg
            if (is_file($dir.$index->id.$imgType)) {
                $existingImages[] = [
                    'src' => WEB_URL.DATA_FOLDER.'booking/'.$index->id.$imgType.'?'.time(),
                    'key' => 1
                ];
            }
            // Check additional images: {id}-2.jpg through {id}-10.jpg
            for ($i = 2; $i <= $maxImages; $i++) {
                if (is_file($dir.$index->id.'-'.$i.$imgType)) {
                    $existingImages[] = [
                        'src' => WEB_URL.DATA_FOLDER.'booking/'.$index->id.'-'.$i.$imgType.'?'.time(),
                        'key' => $i
                    ];
                }
            }
        }

        // Build the image upload HTML
        $imgHtml = '<div class="room-images-upload">';
        $imgHtml .= '<label class="g-input icon-upload"><span>{LNG_Image} ('.Language::replace('max :count images', [':count' => $maxImages]).')</span></label>';
        $imgHtml .= '<div class="room-images-comment">{LNG_Browse image uploaded, type :type} ({LNG_resized automatically})</div>';

        // Existing images preview
        if (!empty($existingImages)) {
            $imgHtml .= '<div class="room-images-preview" id="room_images_preview">';
            foreach ($existingImages as $img) {
                $imgHtml .= '<div class="room-img-item" id="img_item_'.$img['key'].'">';
                $imgHtml .= '<img src="'.$img['src'].'" alt="room image '.$img['key'].'">';
                $imgHtml .= '<button type="button" class="room-img-delete" onclick="deleteRoomImage('.$index->id.', '.$img['key'].')" title="'.Language::get('Delete').'">✕</button>';
                $imgHtml .= '</div>';
            }
            $imgHtml .= '</div>';
        }

        // Available upload slots
        $remainingSlots = $maxImages - count($existingImages);
        $imgHtml .= '<div class="room-images-add" id="room_images_add">';
        $imgHtml .= '<div class="room-img-add-btn" id="room_img_add_trigger">';
        $imgHtml .= '<input type="file" name="pictures[]" id="room_pictures" multiple accept="'.implode(',', array_map(function($t) { return '.'.$t; }, self::$cfg->booking_img_typies)).'" onchange="previewRoomImages(this, '.$maxImages.')">';
        $imgHtml .= '<span class="icon-plus"> {LNG_Add} {LNG_Image}</span>';
        $imgHtml .= '</div>';
        $imgHtml .= '<div id="room_img_new_preview" class="room-images-preview"></div>';
        $imgHtml .= '<div class="room-img-slots"><span id="remaining_slots">'.$remainingSlots.'</span> / '.$maxImages.' {LNG_Image}</div>';
        $imgHtml .= '</div>';
        $imgHtml .= '</div>';

        $fieldset->add('div', [
            'class' => 'item',
            'innerHTML' => $imgHtml
        ]);

        $fieldset = $form->add('fieldset', [
            'class' => 'submit'
        ]);
        // submit
        $fieldset->add('submit', [
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ]);
        // id
        $fieldset->add('hidden', [
            'id' => 'id',
            'value' => $index->id
        ]);
        // existing image count (for JS)
        $fieldset->add('hidden', [
            'id' => 'existing_img_count',
            'value' => count($existingImages)
        ]);
        \Gcms\Controller::$view->setContentsAfter([
            '/:type/' => implode(', ', self::$cfg->booking_img_typies),
            '/:count/' => $maxImages
        ]);
        // คืนค่า HTML
        return $form->render();
    }
}
