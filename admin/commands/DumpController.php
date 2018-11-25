<?

namespace admin\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use admin\models\storage\Dump;
use admin\models\storage\Restore;
use admin\models\Setting;

class DumpController extends Controller {

    public $isArchive;
    public $schemaOnly;
    public $initData;
    public $demoData;
    public $dumpId;
    public $restoreScript;

    public function options($actionID) {
        if ($actionID == 'create') {
            return [
                'isArchive', 'schemaOnly', 'interactive'
            ];
        }
        if ($actionID == 'restore') {
            return [
                'initData', 'demoData', 'restoreScript', 'dumpId', 'interactive'
            ];
        }
    }

    public function actionCreate() {
        $model = new Dump();

        $model->isArchive = $this->isArchive;
        $model->schemaOnly = $this->schemaOnly;

        $this->stdout("Начало создания дампа БД...\n", 94);
        $dbInfo = $this->getDbInfo();
        $dumpOptions = $model->makeDumpOptions();
        $manager = $this->createManager($dbInfo);
        $dumpPath = $manager->makePath($this->path, $dbInfo, $dumpOptions);
        $dumpCommand = $manager->makeDumpCommand($dumpPath, $dbInfo, $dumpOptions);
        Yii::trace(compact('dumpCommand', 'dumpPath', 'dumpOptions'), get_called_class());

        $this->runProcess($dumpCommand);
    }

    public function actionRestore() {
        $model = new Restore();

        $dump_id = $this->dumpId;
        $model->initData = $this->initData;
        $model->demoData = $this->demoData;
        $model->restoreScript = $this->restoreScript;

        $dumpName = basename(ArrayHelper::getValue($this->getFileList(), $dump_id));
        $dumpFile = $this->path . $dumpName;       

        if ($this->confirm(' ')) {
            $this->stdout("Старт восстановления из бэкапа...\n", 94);
            $dbInfo = $this->getDbInfo();
            $restoreOptions = $model->makeRestoreOptions();
            $manager = $this->createManager($dbInfo);
            $restoreCommand = $manager->makeRestoreCommand($dumpFile, $dbInfo, $restoreOptions);
            Yii::trace(compact('restoreCommand', 'dumpFile', 'restoreOptions'), get_called_class());

            if ($this->runProcess($restoreCommand, true)) {
                if ($model->initData) {
                    //При необходимости поместить скрипт для стартовой инициализации                     
                }
                if ($model->demoData) {
                    //При необходимости поместить скрипт с демо-данными
                }
                if ($model->restoreScript) {
                    //Выполняет скрипт @app/dumps/restore.php
                    
                    $restoreScript = $this->path . 'restore.php';
                    if(file_exists($restoreScript))
                    { 
                        $this->stdout("Скрипт ". $restoreScript." запущен...\n", 94);
                        require_once $restoreScript;
                        $this->stdout("Скрипт ". $restoreScript." выполнен!\n", 94);
                    }else
                    {
                        $this->stdout("Скрипт ". $restoreScript." не найден!\n", 94);
                    }
                    
                    
                }
            }
        }
    }

    /**
     * @param $command
     * @param bool $isRestore
     */
    protected function runProcess($command, $isRestore = false) {

        exec($command, $output, $return_var);

        $str_output = "";
        foreach ($output as $value) {
            $str_output .= $value . "\n";
        }

        if (!$return_var) {
            $msg = (!$isRestore) ? Yii::t('admin', "Дамп БД успешно создан!\n") : Yii::t('admin', "Восстановление из бэкапа успешно завершено!\n");
            $this->stdout($msg, 92);
            return true;
        } else {
            $msg = (!$isRestore) ? Yii::t('admin', "Ошибка при создании дампа БД!\n") : Yii::t('admin', "Ошибка при восстановлении из бэкапа!\n");
            $this->stdout($msg . "Команда: " . $command . "\n" . $str_output, 91);
            return false;
        }
    }

    /**
     * Path for backup directory
     *
     * @var string $path
     */
    public $path = '';

    /**
     * @var callable|Closure $createManagerCallback
     * argument - dbInfo; expected reply - instance of bs\dbManager\contracts\IDumpManager or false, for default
     * @example
     * 'createManagerCallback' => function($dbInfo) {
     *     if($dbInfo['dbName'] == 'exclusive') {
     *         return new MyExclusiveManager;
     *     } else {
     *         return false;
     *     }
     * }
     */
    public $createManagerCallback;

    /**
     * @var array
     */
    protected $dbInfo = [];

    /**
     * @var array
     */
    protected $fileList = [];

    /**
     * @throws InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     */
    public function init() {
        parent::init();

        $this->path = Yii::getAlias(Setting::get('path_dumps'));
        if (!StringHelper::endsWith($this->path, '/', false)) {
            $this->path .= '/';
        }
        if (!is_dir($this->path)) {
            throw new InvalidConfigException(Yii::t('admin', 'Не найден путь'));
        }
        if (!is_writable($this->path)) {
            throw new InvalidConfigException(Yii::t('admin', 'Отстутствуют права на запись! Проверить chmod!'));
        }
        $this->fileList = FileHelper::findFiles($this->path, ['only' => ['*.sql', '*.gz']]);
    }

    public function getDbInfo() {

        $this->dbInfo['driverName'] = Yii::$app->db->driverName;
        $this->dbInfo['dsn'] = Yii::$app->db->dsn;
        $this->dbInfo['host'] = $this->getDsnAttribute('host', Yii::$app->db->dsn);
        $this->dbInfo['port'] = $this->getDsnAttribute('port', Yii::$app->db->dsn);
        $this->dbInfo['dbName'] = $this->getDsnAttribute('dbname', Yii::$app->db->dsn);
        $this->dbInfo['username'] = Yii::$app->db->username;
        $this->dbInfo['password'] = Yii::$app->db->password;
        $this->dbInfo['prefix'] = Yii::$app->db->tablePrefix;

        return $this->dbInfo;
    }

    public function getFileList() {
        return $this->fileList;
    }

    /**
     * @param array $dbInfo
     * @return IDumpManager
     * @throws NotSupportedException
     */
    public function createManager($dbInfo) {
        if (is_callable($this->createManagerCallback)) {
            $result = call_user_func($this->createManagerCallback, $dbInfo);
            if ($result !== false) {
                return $result;
            }
        }
        if ($dbInfo['driverName'] === 'mysql') {
            return new \admin\models\storage\MysqlDumpManager();
        } elseif ($dbInfo['driverName'] === 'pgsql') {
            return new \admin\models\storage\PostgresManagerClass();
        } else {
            throw new NotSupportedException(Yii::t('admin', 'Драйвер БД не поддерживается!'));
        }
    }

    /**
     * @param $name
     * @param $dsn
     * @return null
     */
    protected function getDsnAttribute($name, $dsn) {
        if (preg_match('/' . $name . '=([^;]*)/', $dsn, $match)) {
            return $match[1];
        } else {
            return null;
        }
    }

}
