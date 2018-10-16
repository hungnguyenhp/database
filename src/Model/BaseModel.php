<?php
/**
 * Project database.
 * Created by PhpStorm.
 * User: 713uk13m <dev@nguyenanhung.com>
 * Date: 10/16/18
 * Time: 11:22
 */

namespace nguyenanhung\MyDatabase\Model;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use nguyenanhung\MyDebug\Debug;
use nguyenanhung\MyDatabase\Interfaces\ProjectInterface;
use nguyenanhung\MyDatabase\Interfaces\BaseModelInterface;

/**
 * Class BaseModel
 *
 * @package   nguyenanhung\MyDatabase\Model
 * @author    713uk13m <dev@nguyenanhung.com>
 * @copyright 713uk13m <dev@nguyenanhung.com>
 */
class BaseModel implements ProjectInterface, BaseModelInterface
{
    /** @var object Đối tượng khởi tạo dùng gọi đến Class Debug \nguyenanhung\MyDebug\Debug */
    protected $debug;
    /** @var array|null Mảng dữ liệu chứa thông tin database cần kết nối tới */
    protected $db;
    /** @var string|null Bảng cần lấy dữ liệu */
    protected $table;
    /** @var object Đối tượng khởi tạo dùng gọi đến Class Capsule Manager \Illuminate\Database\Capsule\Manager */
    protected $capsule;
    /** @var bool Cấu hình trạng thái Debug, TRUE nếu bật, FALSE nếu tắt */
    public $debugStatus = FALSE;
    /**
     * @var null|string Cấu hình Level Debug
     * @see https://github.com/nguyenanhung/my-debug/blob/master/src/Interfaces/DebugInterface.php
     */
    public $debugLevel = NULL;
    /** @var null|bool|string Cấu hình thư mục lưu trữ Log, VD: /your/to/path */
    public $debugLoggerPath = NULL;
    /** @var null|string Cấu hình File Log, VD: Log-2018-10-15.log | Log-date('Y-m-d').log */
    public $debugLoggerFilename = NULL;
    /** @var string Primary Key Default */
    public $primaryKey = 'id';

    /**
     * BaseModel constructor.
     */
    public function __construct()
    {
        $this->debug = new Debug();
        if ($this->debugStatus === TRUE) {
            $this->debug->setDebugStatus($this->debugStatus);
            if ($this->debugLevel) {
                $this->debug->setGlobalLoggerLevel($this->debugLevel);
            }
            if ($this->debugLoggerPath) {
                $this->debug->setLoggerPath($this->debugLoggerPath);
            }
            if (empty($this->debugLoggerFilename)) {
                $this->debugLoggerFilename = 'Log-' . date('Y-m-d') . '.log';
            }
            $this->debug->setLoggerSubPath(__CLASS__);
            $this->debug->setLoggerFilename($this->debugLoggerFilename);
        }
    }

    /**
     * BaseModel destructor.
     */
    public function __destruct()
    {
    }

    /**
     * Function getVersion
     *
     * @author  : 713uk13m <dev@nguyenanhung.com>
     * @time    : 10/16/18 11:42
     *
     * @return mixed|string Current Project Version
     * @example 0.1.0
     */
    public function getVersion()
    {
        return self::VERSION;
    }

    /**
     * Hàm khởi tạo kết nối đến Cơ sở dữ liệu
     *
     * Sử dụng đối tượng DB được truyền từ bên ngoài vào
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 15:47
     *
     */
    public function connection()
    {
        $this->capsule = new Capsule;
        $this->capsule->addConnection($this->db);
        $this->capsule->setEventDispatcher(new Dispatcher(new Container));
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    /**
     * Hàm set và kết nối cơ sở dữ liệu
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 11:43
     *
     * @param array $db Mảng dữ liệu thông tin DB cần kết nối
     */
    public function setDatabase($db = [])
    {
        $this->db = $db;
    }

    /**
     * Hàm set và kết nối đến bảng dữ liệu
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 11:43
     *
     * @param string $table Bảng cần lấy dữ liệu
     */
    public function setTable($table = '')
    {
        $this->table = $table;
    }

    /**
     * Hàm truncate bảng dữ liệu
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 14:15
     *
     */
    public function truncate()
    {
        Capsule::table($this->table)->truncate();
    }

    /**
     * Hàm đếm toàn bộ bản ghi tồn tại trong bảng
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 11:43
     *
     * @return int
     */
    public function countAll()
    {
        $this->connection();
        $db = Capsule::table($this->table);
        $this->debug->info(__FUNCTION__, 'SQL Queries: ' . $db->toSql());

        return $db->count();
    }

    /**
     * Hàm kiểm tra sự tồn tại bản ghi theo tham số đầu vào
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 11:45
     *
     * @param string $value Giá trị cần kiểm tra
     * @param string $field Field tương ứng, ví dụ: ID
     *
     * @return int Số lượng bàn ghi tồn tại phù hợp với điều kiện đưa ra
     */
    public function checkExists($value = '', $field = 'id')
    {
        $this->connection();
        $db = Capsule::table($this->table)->where($field, '=', $value);
        $this->debug->info(__FUNCTION__, 'SQL Queries: ' . $db->toSql());

        return $db->count();
    }

    /**
     * Hàm lấy thông tin bản ghi theo tham số đầu vào
     *
     * Đây là hàm cơ bản, chỉ áp dụng check theo 1 field
     *
     * Lấy bản ghi đầu tiên phù hợp với điều kiện
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 11:51
     *
     * @param string      $value  Giá trị cần kiểm tra
     * @param string      $field  Field tương ứng, ví dụ: ID
     * @param null|string $format Format dữ liệu đầu ra: null, json, array, base, result
     *
     * @return array|\Illuminate\Support\Collection|string Mảng|String|Object dữ liều phụ hợp với yêu cầu
     *                                                     map theo biến format truyền vào
     */
    public function getInfo($value = '', $field = 'id', $format = NULL)
    {
        $this->connection();
        $format = strtolower($format);
        $db     = Capsule::table($this->table);
        if (is_array($value) && count($value) > 0) {
            foreach ($value as $f => $v) {
                if (is_array($v)) {
                    $db->whereIn($f, $v);
                } else {
                    $db->where($f, '=', $v);
                }
            }
        } else {
            $db->where($field, '=', $value);
        }
        $this->debug->info(__FUNCTION__, 'SQL Queries: ' . $db->toSql());
        if ($format == 'result') {
            $result = $db->get();
        } else {
            $result = $db->first();
        }
        if ($format == 'json') {
            return $result->toJson();
        } elseif ($format == 'array') {
            return $result->toArray();
        } elseif ($format == 'base') {
            return $result->toBase();
        } else {
            return $result;
        }
    }

    /**
     * Hàm lấy giá trị 1 field của bản ghi dựa trên điều kiện 1 bản ghi đầu vào
     *
     * Đây là hàm cơ bản, chỉ áp dụng check theo 1 field
     *
     * Lấy bản ghi đầu tiên phù hợp với điều kiện
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 11:51
     *
     * @param string $value       Giá trị cần kiểm tra
     * @param string $field       Field tương ứng với giá tri kiểm tra, ví dụ: ID
     * @param string $fieldOutput field kết quả đầu ra
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|mixed|null|object
     */
    public function getValue($value = '', $field = 'id', $fieldOutput = '')
    {
        $this->connection();
        $db = Capsule::table($this->table);
        if (is_array($value) && count($value) > 0) {
            foreach ($value as $f => $v) {
                if (is_array($v)) {
                    $db->whereIn($f, $v);
                } else {
                    $db->where($f, '=', $v);
                }
            }
        } else {
            $db->where($field, '=', $value);
        }
        $this->debug->info(__FUNCTION__, 'SQL Queries: ' . $db->toSql());
        $result = $db->first();
        if (!empty($fieldOutput) && isset($result->$fieldOutput)) {
            return $result->$fieldOutput;
        } else {
            return $result;
        }
    }

    /**
     * Hàm lấy danh sách Distinct toàn bộ bản ghi trong 1 bảng
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 13:59
     *
     * @param string $field Mảng dữ liệu danh sách các field cần so sánh
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDistinctResult($field = '')
    {
        if (!is_array($field)) {
            $field = [$field];
        }
        $this->connection();
        $db = Capsule::table($this->table);
        $db->distinct();
        $this->debug->info(__FUNCTION__, 'SQL Queries: ' . $db->toSql());
        $result = $db->get($field);

        return $result;
    }

    /**
     * Hàm thêm mới bản ghi vào bảng
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 14:04
     *
     * @param array $data Mảng chứa dữ liệu cần insert
     *
     * @return int Insert ID của bản ghi
     */
    public function add($data = [])
    {
        $this->connection();
        $db = Capsule::table($this->table);
        $this->debug->info(__FUNCTION__, 'SQL Queries: ' . $db->toSql());
        $id = $db->insertGetId($data);

        return $id;
    }

    /**
     * Hàm update dữ liệu
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 14:10
     *
     * @param array        $data   Mảng dữ liệu cần Update
     * @param array|string $wheres Mãng dữ liệu hoặc giá trị primaryKey cần so sánh điều kiện để update
     *
     * @return int Số bản ghi được update thỏa mãn với điều kiện đầu vào
     */
    public function update($data = [], $wheres = [])
    {
        $this->connection();
        $db = Capsule::table($this->table);
        if (is_array($wheres) && count($wheres) > 0) {
            foreach ($wheres as $field => $value) {
                if (is_array($value)) {
                    $db->whereIn($field, $value);
                } else {
                    $db->where($field, '=', $value);
                }
            }
        } else {
            $db->where($this->primaryKey, '=', $wheres);
        }
        $this->debug->info(__FUNCTION__, 'SQL Queries: ' . $db->toSql());
        $result = $db->update($data);

        return $result;
    }

    /**
     * Hàm xóa dữ liệu
     *
     * @author: 713uk13m <dev@nguyenanhung.com>
     * @time  : 10/16/18 14:13
     *
     * @param array|string $wheres Mãng dữ liệu hoặc giá trị primaryKey cần so sánh điều kiện để update
     *
     * @return int Số bản ghi đã xóa
     */
    public function delete($wheres = [])
    {
        $this->connection();
        $db = Capsule::table($this->table);
        if (is_array($wheres) && count($wheres) > 0) {
            foreach ($wheres as $field => $value) {
                if (is_array($value)) {
                    $db->whereIn($field, $value);
                } else {
                    $db->where($field, '=', $value);
                }
            }
        } else {
            $db->where($this->primaryKey, '=', $wheres);
        }
        $this->debug->info(__FUNCTION__, 'SQL Queries: ' . $db->toSql());
        $result = $db->delete();

        return $result;
    }
}
