<?php
/**
 * @filesource modules/booking/views/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Booking\Report;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=booking-report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Booking\Tools\View
{
    /**
     * @var array
     */
    private $status;
    /**
     * @var object
     */
    private $category;
    /**
     * @var array
     */
    private $topic = ['topic' => ''];

    /**
     * รายงานการจอง (แอดมิน)
     *
     * @param Request $request
     * @param array  $params
     *
     * @return string
     */
    public function render(Request $request, $params)
    {
        $this->category = \Booking\Category\Model::init();
        $this->status = $params['booking_status'];
        unset($params['booking_status']);
        $hideColumns = ['id', 'today', 'begin', 'end', 'color', 'remain', 'phone', 'approve'];
        // filter
        $filters = [
            [
                'name' => 'from',
                'type' => 'date',
                'text' => '{LNG_from}',
                'value' => $params['from']
            ],
            [
                'name' => 'to',
                'type' => 'date',
                'text' => '{LNG_to}',
                'value' => $params['to']
            ],
            [
                'name' => 'room_id',
                'default' => 0,
                'text' => '{LNG_Room}',
                'options' => [0 => '{LNG_all items}']+\Booking\Room\Model::toSelect(),
                'value' => $params['room_id']
            ]
        ];
        foreach (Language::get('BOOKING_SELECT', []) as $key => $label) {
            if (!$this->category->isEmpty($key)) {
                $params[$key] = $request->request($key)->toInt();
                $filters[] = [
                    'name' => $key,
                    'text' => $label,
                    'options' => [0 => '{LNG_all items}'] + $this->category->toSelect($key),
                    'value' => $params[$key]
                ];
                $this->topic[] = $label;
                $this->topic[$key] = '';
                $hideColumns[] = $label;
            }
        }
        $filters[] = [
            'name' => 'status',
            'text' => '{LNG_Status}',
            'options' => [-1 => '{LNG_all items}'] + $this->status,
            'value' => $params['status']
        ];
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable([
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Booking\Report\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('bookingReport_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('bookingReport_sort', 'create_date')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => [$this, 'onRow'],
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => $hideColumns,
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => ['topic', 'name', 'contact', 'phone'],
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/booking/model/report/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => [
                [
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => [
                        'delete' => '{LNG_Delete}'
                    ]
                ]
            ],
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => $filters,
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => [
                'topic' => [
                    'text' => '{LNG_Topic}'
                ],
                'name' => [
                    'text' => '{LNG_Room name}',
                    'sort' => 'name'
                ],
                'contact' => [
                    'text' => '{LNG_Contact name}'
                ],
                'create_date' => [
                    'text' => '{LNG_Created}',
                    'class' => 'center',
                    'sort' => 'create_date'
                ],
                'status' => [
                    'text' => '{LNG_Status}',
                    'class' => 'center'
                ],
                'reason' => [
                    'text' => '{LNG_Reason}'
                ]
            ],
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => [
                'contact' => [
                    'class' => 'nowrap small'
                ],
                'create_date' => [
                    'class' => 'center small'
                ],
                'status' => [
                    'class' => 'center'
                ]
            ],
            /* ฟังก์ชั่นตรวจสอบการแสดงผลปุ่มในแถว */
            'onCreateButton' => [$this, 'onCreateButton'],
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => [
                'edit' => [
                    'class' => 'icon-valid button green notext',
                    'href' => $uri->createBackUri(['module' => 'booking-approve', 'id' => ':id']),
                    'title' => '{LNG_Approve}/{LNG_Edit}'
                ]
            ]
        ]);
        // save cookie
        setcookie('bookingReport_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('bookingReport_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        if ($item['today'] == 1) {
            $prop->class = 'bg3';
        }
        if ($item['phone'] != '') {
            $item['contact'] .= '<br><a class=icon-phone href="tel:'.$item['phone'].'">'.$item['phone'].'</a>';
        }
        $item['name'] = '<span class=term style="background-color:'.$item['color'].'">'.$item['name'].'</span>';
        $item['name'] .= '<div class="small nowrap">'.self::dateRange($item).'</div>';
        $item['create_date'] = '<span class=small>'.Date::format($item['create_date'], 'd M Y').'<br>{LNG_Time} '.Date::format($item['create_date'], 'H:i').'</span>';
        foreach ($this->category->items() as $k => $v) {
            if (isset($item[$v])) {
                $this->topic[$k] = $this->category->get($k, $item[$v]);
            }
        }
        $item['topic'] = '<div class=two_lines><b>'.$item['topic'].'</b><small class=block>'.implode(' ', $this->topic).'</small></div>';
        $item['reason'] = '<span class="two_lines small" title="'.$item['reason'].'">'.$item['reason'].'</span>';
        $item['status'] = self::showStatus($this->status, $item['status']);
        return $item;
    }

    /**
     * ฟังกชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่
     *
     * @param string $btn
     * @param array $attributes
     * @param array $item
     *
     * @return array
     */
    public function onCreateButton($btn, $attributes, $item)
    {
        if ($btn == 'edit') {
            if (empty(self::$cfg->booking_approving) && $item['today'] == 2) {
                return false;
            } elseif (self::$cfg->booking_approving == 1 && $item['remain'] < 0) {
                return false;
            } else {
                return $attributes;
            }
        } else {
            return $attributes;
        }
    }
}
