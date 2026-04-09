<?php
/**
 * @filesource modules/booking/models/room.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Booking\Room;

/**
 * โมเดลสำหรับ (rooms.php)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * Query ห้องประชุม ใส่ลงใน select
     *
     * @param bool $published
     * @param int $room_id
     *
     * @return array
     */
    public static function toSelect($published = true, $room_id = 0)
    {
        $where = [];
        if ($published) {
            $where[] = ['published', 1];
        }
        if ($room_id > 0) {
            $where[] = ['id', $room_id];
        }
        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'name')
            ->from('rooms')
            ->where($where, 'OR')
            ->order("SQL(CASE WHEN name LIKE '%ขุมทอง%' THEN 1 WHEN name LIKE '%ชั้น 2%' OR name LIKE '%ชั้น2%' THEN 2 WHEN name LIKE '%อาทิต%' THEN 3 WHEN name LIKE '%อักษร%' THEN 5 WHEN name LIKE '%สาสนะ%' THEN 6 ELSE 4 END)", 'name')
            ->cacheOn();
        $result = [];
        foreach ($query->execute() as $item) {
            $result[$item->id] = $item->name;
        }
        return $result;
    }
}
