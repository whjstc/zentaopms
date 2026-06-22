<?php
declare(strict_types=1);
/**
 * The control file of task module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     task
 * @version     $Id: control.php 5106 2013-07-12 01:28:54Z chencongzhi520@gmail.com $
 * @link        https://www.zentao.net
 */
class task extends control
{
    /**
     * Lark sync request log context.
     *
     * @var array
     * @access private
     */
    private array $larkSyncLog = array();

    /**
     * Construct function, load model of project and story modules.
     *
     * @access public
     * @return void
     */
    public function __construct($module = '', $method = '')
    {
        parent::__construct($module, $method);
        $this->loadModel('project');
        $this->loadModel('execution');
        $this->loadModel('story');
        $this->loadModel('tree');
    }

    /**
     * 创建一个任务。
     * Create a task.
     *
     * @param  int    $executionID
     * @param  int    $storyID
     * @param  int    $moduleID
     * @param  int    $taskID
     * @param  int    $todoID
     * @param  string $cardPosition
     * @param  int    $bugID
     * @access public
     * @return void
     */
    public function create(int $executionID = 0, int $storyID = 0, int $moduleID = 0, int $taskID = 0, int $todoID = 0, string $cardPosition = '', int $bugID = 0)
    {
        /* Analytic parameter. */
        $cardPosition = str_replace(array(',', ' '), array('&', ''), $cardPosition);
        parse_str($cardPosition, $output);

        $this->session->set('executionStoryList', $this->app->getURI(true), 'execution');

        /* Set menu and get execution information. */
        $executionID = $this->taskZen->setMenu($executionID);
        $execution   = $this->execution->getById($executionID);

        /* If you do not have permission to access any execution, go to the create execution page. */
        if(!$this->execution->checkPriv($executionID)) $this->locate($this->createLink('execution', 'create'));

        /* Check whether the execution has permission to create tasks. */
        if($this->taskZen->isLimitedInExecution($executionID)) return $this->send(array('load' => array('locate' => $this->createLink('execution', 'task', "executionID={$executionID}"), 'alert' => sprintf($this->lang->task->createDenied, $execution->multiple ? $this->lang->executionCommon : $this->lang->projectCommon))));

        /* Submit the data process after create the task form. */
        if(!empty($_POST))
        {
            $taskData = $this->taskZen->buildTaskForCreate($this->post->execution ? (int)$this->post->execution : $executionID);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $this->dao->begin();
            if($this->post->type == 'test' && $this->post->selectTestStory == 'on')
            {
                /* Prepare to create the data for the test subtask and to check the data format. */
                $testTasks  = $this->taskZen->buildTestTasksForCreate($taskData->execution);
                if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

                $taskIdList = $this->task->createTaskOfTest($taskData, $testTasks);
            }
            elseif($this->post->type == 'affair')
            {
                $taskIdList = $this->task->createTaskOfAffair($taskData, is_array($this->post->assignedTo) ? $this->post->assignedTo : array($this->post->assignedTo));
            }
            elseif($this->post->multiple)
            {
                $teamData   = form::data($this->config->task->form->team->create)->get();
                $taskIdList = $this->task->createMultiTask($taskData, $teamData);
            }
            else
            {
                $taskIdList = $this->task->create($taskData);
            }

            if(dao::isError())
            {
                $this->dao->rollBack();
                return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            }

            /* Update other data related to the task after it is created. */
            $columnID     = isset($output['columnID']) ? (int)$output['columnID'] : 0;
            $taskIdList   = (array)$taskIdList;
            $taskData->id = current($taskIdList);
            $this->task->afterCreate($taskData, $taskIdList, $bugID, $todoID);
            if($this->post->lane) $this->task->updateKanbanData($taskData->execution, $taskIdList, (int)$this->post->lane, $columnID);
            setCookie("lastTaskModule", (string)$this->post->module, $this->config->cookieLife, $this->config->webRoot, '', $this->config->cookieSecure, true);

            if(!empty($_POST['fileList']))
            {
                $fileList = $this->post->fileList;
                if($fileList) $fileList = json_decode($fileList, true);
                $this->loadModel('file')->saveDefaultFiles($fileList, 'task', $taskIdList);
            }

            /* Get the information returned after a task is created. */
            $response = $this->taskZen->responseAfterCreate($taskData, $execution, $this->post->after ? $this->post->after : '');
            return $this->send($response);
        }

        /* Shows the variables needed to create the task page. */
        $this->taskZen->assignCreateVars($execution, $storyID, $moduleID, $taskID, $todoID, $bugID, $output, $cardPosition);
    }

    /**
     * 批量创建任务。
     * Batch create tasks.
     *
     * @param  int    $executionID
     * @param  int    $storyID
     * @param  int    $moduleID
     * @param  int    $taskID
     * @param  string $cardPosition
     * @access public
     * @return void
     */
    public function batchCreate(int $executionID, int $storyID = 0, int $moduleID = 0, int $taskID = 0, string $cardPosition = '')
    {
        /* Analytic parameter. */
        $cardPosition = str_replace(array(',', ' '), array('&', ''), $cardPosition);
        parse_str($cardPosition, $output);

        $this->taskZen->setMenu($executionID);
        $execution = $this->execution->getById($executionID);

        /* Check whether the execution has permission to create tasks. */
        if($this->taskZen->isLimitedInExecution($executionID)) return $this->send(array('load' => array('locate' => $this->createLink('execution', 'task', "executionID={$executionID}"), 'alert' => sprintf($this->lang->task->createDenied, $execution->multiple ? $this->lang->executionCommon : $this->lang->projectCommon))));

        if(!empty($_POST))
        {
            $parent = $taskID > 0 ? $this->task->fetchById($taskID) : null;
            /* Process the request data for the batch create tasks. */
            $taskData = $this->taskZen->buildTasksForBatchCreate($execution, $taskID, $output);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $taskIdList = $this->task->batchCreate($taskData, $output);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            /* Update other data related to the task after it is created. */
            $this->task->afterBatchCreate($taskIdList, $parent);
            if(!isset($output['laneID']) || !isset($output['columnID'])) $this->loadModel('kanban')->updateLane($executionID, 'task');

            $response = $this->taskZen->responseAfterbatchCreate($taskIdList, $execution);
            return $this->send($response);
        }

        $this->taskZen->buildBatchCreateForm($execution, $storyID, $moduleID, $taskID, $output);
    }

    /**
     * 编辑一个任务。
     * Edit a task.
     *
     * @param  int    $taskID
     * @param  string $from   ''|taskkanban
     * @access public
     * @return void
     */
    public function edit(int $taskID, string $from = '')
    {
        $this->taskZen->commonAction($taskID);

        if(!empty($_POST))
        {
            /* Prepare and check data. */
            $task = form::data($this->config->task->form->edit)->add('id', $taskID)->get();
            $task = $this->taskZen->buildTaskForEdit($task);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            /* For team task. */
            $teamData = $this->post->team ? form::data($this->config->task->form->team->edit)->get() : new stdclass();

            /* Update task. */
            $changes = $this->task->update($task, $teamData);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            /* Execute hooks and synchronize the status of related objects. */
            $message = $this->executeHooks($taskID);
            $message = $message ?: $this->lang->saveSuccess;
            if($task->status == 'doing') $this->loadModel('common')->syncPPEStatus($taskID);

            $response = $this->taskZen->responseAfterEdit($taskID, $from, $changes, $message);
            return $this->send($response);
        }

        $this->taskZen->buildEditForm($taskID);
    }

    /**
     * Sync a task from Lark Base automation.
     *
     * @access public
     * @return void
     */
    public function syncFromLark()
    {
        $this->initLarkSyncLog();
        $this->appendLarkSyncLogQueryParams();

        if(strtoupper((string)$_SERVER['REQUEST_METHOD']) != 'POST') return $this->sendLarkSyncResponse(false, 'Only POST is allowed.', array(), 405);
        if(!$this->checkLarkSyncToken()) return $this->sendLarkSyncResponse(false, 'Invalid sync token.', array(), 401);

        $payload = $this->getLarkSyncPayload();
        if(!$payload)
        {
            $taskID = $this->extractLarkTaskIDFromRawBody();
            $extra  = $taskID ? array('taskID' => $taskID, 'taskLink' => $this->buildTaskLink($taskID)) : array();
            return $this->sendLarkSyncResponse(false, 'Invalid JSON request body.', $extra);
        }
        $this->appendLarkSyncLogPayload($payload);

        $syncUser = $this->initLarkSyncUser();
        if(!$syncUser) return $this->sendLarkSyncResponse(false, 'Sync account is not configured or does not exist.', array(), 500);
        $this->larkSyncLog['syncAccount'] = $syncUser->account;

        $data = $this->normalizeLarkTaskData($payload);
        if(!empty($data->error))
        {
            $this->appendLarkSyncLogData($data);
            return $this->sendLarkSyncResponse(false, $data->error);
        }

        dao::$errors = array();
        $_POST['uid']     = '';
        $_POST['comment'] = '飞书多维表格同步';

        if($data->taskID)
        {
            $oldTask = $this->task->getByID($data->taskID);
            if(!$oldTask || !empty($oldTask->deleted)) return $this->sendLarkSyncResponse(false, "Task {$data->taskID} does not exist.");

            $this->completeLarkTaskDates($data, $oldTask);
            $this->appendLarkSyncLogData($data);
            if(!empty($data->error)) return $this->sendLarkSyncResponse(false, $data->error, array('taskID' => $data->taskID, 'taskLink' => $this->buildTaskLink($data->taskID)));

            $task = clone $oldTask;
            $this->applyLarkDataToTask($task, $data, $oldTask);
            $this->sanitizeLarkTaskForUpdate($task);
            $changes = $this->task->update($task, new stdclass());
            if(dao::isError()) return $this->sendLarkSyncResponse(false, dao::getError(), array('taskID' => $data->taskID));

            return $this->sendLarkSyncResponse(true, $this->lang->saveSuccess, array('action' => 'updated', 'taskID' => $data->taskID, 'taskLink' => $this->buildTaskLink($data->taskID), 'changes' => $changes));
        }

        $this->completeLarkTaskDates($data);
        $this->appendLarkSyncLogData($data);
        if(!empty($data->error)) return $this->sendLarkSyncResponse(false, $data->error);

        $execution = $this->dao->select('*')->from(TABLE_PROJECT)->where('id')->eq($data->execution)->andWhere('type')->in('stage,sprint,kanban')->fetch();
        if(!$execution) return $this->sendLarkSyncResponse(false, "Execution {$data->execution} does not exist.");

        $taskID = $this->createLarkTask($data, $execution);
        if(!$taskID || dao::isError()) return $this->sendLarkSyncResponse(false, dao::getError());

        return $this->sendLarkSyncResponse(true, $this->lang->saveSuccess, array('action' => 'created', 'taskID' => $taskID, 'taskLink' => $this->buildTaskLink($taskID)));
    }

    /**
     * Check sync token.
     *
     * @access private
     * @return bool
     */
    private function checkLarkSyncToken(): bool
    {
        $expected = (string)zget($this->config->task->larkSync, 'token', '');
        if($expected == '') $expected = (string)getenv('LARK_ZENTAO_SYNC_TOKEN');
        if($expected == '') return false;

        $actual = (string)zget($_SERVER, 'HTTP_X_LARK_ZENTAO_TOKEN', '');
        if($actual == '' && !empty($_SERVER['HTTP_AUTHORIZATION']))
        {
            $authorization = (string)$_SERVER['HTTP_AUTHORIZATION'];
            if(stripos($authorization, 'Bearer ') === 0) $actual = trim(substr($authorization, 7));
        }

        return function_exists('hash_equals') ? hash_equals($expected, $actual) : $expected === $actual;
    }

    /**
     * Get JSON payload.
     *
     * @access private
     * @return object|null
     */
    private function getLarkSyncPayload(): ?object
    {
        $raw = file_get_contents('php://input');
        $this->larkSyncLog['rawBody'] = $this->truncateLarkSyncLogValue((string)$raw);
        $this->larkSyncLog['contentType'] = (string)zget($_SERVER, 'CONTENT_TYPE', '');

        $payload = json_decode((string)$raw);
        $jsonError = json_last_error_msg();
        if(!is_object($payload) && $this->hasUnescapedJsonControlChars((string)$raw))
        {
            $payload = json_decode($this->escapeJsonControlCharsInStrings((string)$raw));
            if(is_object($payload)) $jsonError = '';
        }
        if(!is_object($payload))
        {
            $payload = $this->parseLarkLooseJsonObject((string)$raw);
            if(is_object($payload)) $jsonError = '';
        }
        if(!is_object($payload) && !empty($_POST)) $payload = (object)$_POST;
        $this->larkSyncLog['jsonError'] = is_object($payload) ? '' : json_last_error_msg();
        if(!is_object($payload)) $this->larkSyncLog['jsonError'] = $jsonError;
        return is_object($payload) ? $payload : null;
    }

    /**
     * Check whether raw JSON likely contains unescaped control characters in strings.
     *
     * @param  string    $raw
     * @access private
     * @return bool
     */
    private function hasUnescapedJsonControlChars(string $raw): bool
    {
        return json_last_error() === JSON_ERROR_CTRL_CHAR || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $raw);
    }

    /**
     * Escape control characters that Feishu may put directly inside JSON string values.
     *
     * @param  string    $raw
     * @access private
     * @return string
     */
    private function escapeJsonControlCharsInStrings(string $raw): string
    {
        $escaped = '';
        $inString = false;
        $isEscaped = false;
        $length = strlen($raw);

        for($i = 0; $i < $length; $i++)
        {
            $char = $raw[$i];
            $ord  = ord($char);

            if($char === '"' && !$isEscaped) $inString = !$inString;
            if($inString && $ord < 32)
            {
                if($char === "\n") $escaped .= '\n';
                elseif($char === "\r") $escaped .= '\r';
                elseif($char === "\t") $escaped .= '\t';
                else $escaped .= sprintf('\u%04x', $ord);
            }
            else
            {
                $escaped .= $char;
            }

            $isEscaped = $char === '\\' && !$isEscaped;
            if($char !== '\\') $isEscaped = false;
        }

        return $escaped;
    }

    /**
     * Parse Feishu raw JSON object when field values contain unescaped quotes.
     *
     * @param  string      $raw
     * @access private
     * @return object|null
     */
    private function parseLarkLooseJsonObject(string $raw): ?object
    {
        if(trim($raw) == '' || strpos($raw, '{') === false) return null;
        if(!preg_match_all('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"\\s*:/u', $raw, $matches, PREG_OFFSET_CAPTURE)) return null;

        $payload = new stdclass();
        $count   = count($matches[0]);
        $length  = strlen($raw);

        for($i = 0; $i < $count; $i++)
        {
            $key       = $this->decodeLarkLooseString($matches[1][$i][0]);
            $valueFrom = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $valueTo   = $i + 1 < $count ? $matches[0][$i + 1][1] : strrpos($raw, '}');
            if($valueTo === false || $valueTo <= $valueFrom) $valueTo = $length;

            $value = trim(substr($raw, $valueFrom, $valueTo - $valueFrom));
            $value = rtrim($value);
            if(substr($value, -1) === ',') $value = rtrim(substr($value, 0, -1));

            $payload->{$key} = $this->parseLarkLooseJsonValue($value);
        }

        return $payload;
    }

    /**
     * Parse a loose JSON scalar value.
     *
     * @param  string    $value
     * @access private
     * @return mixed
     */
    private function parseLarkLooseJsonValue(string $value): mixed
    {
        $value = trim($value);
        if($value === '') return '';
        if($value === 'null') return null;
        if($value === 'true') return true;
        if($value === 'false') return false;
        if(is_numeric($value)) return strpos($value, '.') === false ? (int)$value : (float)$value;

        if($value[0] === '"' && substr($value, -1) === '"') $value = substr($value, 1, -1);
        return $this->decodeLarkLooseString($value);
    }

    /**
     * Decode common JSON string escapes without rejecting unescaped quotes.
     *
     * @param  string    $value
     * @access private
     * @return string
     */
    private function decodeLarkLooseString(string $value): string
    {
        $value = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/u', function($matches)
        {
            $code = hexdec($matches[1]);
            if($code < 0x80) return chr($code);
            if($code < 0x800) return chr(0xC0 | ($code >> 6)) . chr(0x80 | ($code & 0x3F));
            return chr(0xE0 | ($code >> 12)) . chr(0x80 | (($code >> 6) & 0x3F)) . chr(0x80 | ($code & 0x3F));
        }, $value);

        return strtr($value, array(
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\"' => '"',
            '\\/' => '/',
            '\\\\' => '\\'
        ));
    }

    /**
     * Extract task ID from invalid raw JSON.
     *
     * @access private
     * @return int
     */
    private function extractLarkTaskIDFromRawBody(): int
    {
        $raw = (string)zget($this->larkSyncLog, 'rawBody', '');
        if($raw == '') return 0;

        if(!preg_match('/"(?:zentaoTaskID|禅道任务 ID|禅道任务ID)"\s*:\s*"?(\\d+)"?/u', $raw, $matches)) return 0;
        return (int)$matches[1];
    }

    /**
     * Initialize current user for sync actions.
     *
     * @access private
     * @return object|false
     */
    private function initLarkSyncUser(): object|false
    {
        $account = (string)zget($this->config->task->larkSync, 'account', 'admin');
        $user = $this->dao->select('*')->from(TABLE_USER)->where('account')->eq($account)->andWhere('deleted')->eq('0')->fetch();
        if(!$user) return false;

        $this->app->user = $user;
        $_SESSION['user'] = $user;
        return $user;
    }

    /**
     * Normalize Lark payload to ZenTao task data.
     *
     * @param  object    $payload
     * @access private
     * @return object
     */
    private function normalizeLarkTaskData(object $payload): object
    {
        $data = new stdclass();
        $data->recordID    = $this->getLarkValue($payload, array('record_id', 'recordID', '飞书记录ID'));
        $data->taskID      = (int)$this->getLarkValue($payload, array('zentaoTaskID', 'zentao_task_id', 'taskID', '禅道任务 ID', '禅道任务ID'));
        $data->execution   = (int)($this->getLarkValue($payload, array('execution', 'executionID', '执行ID')) ?: zget($this->config->task->larkSync, 'execution', 37));
        $data->name        = $this->normalizeLarkTaskName($this->getLarkValue($payload, array('title', 'name', '任务标题')));
        $data->desc        = (string)$this->getLarkValue($payload, array('desc', 'description', '任务描述'));
        $data->assignedTo  = $this->resolveLarkAssignee($this->getLarkValue($payload, array('assignedTo', 'assignee', '负责人', '执行人', '飞书负责人')));
        $data->openedDate  = $this->normalizeLarkDateTime($this->getLarkValue($payload, array('createdDate', 'created_time', '创建日期', '创建时间')));
        $data->estStarted  = $this->normalizeLarkDate($this->getLarkValue($payload, array('estStarted', 'startTime', '开始时间', '预计开始', '预计开始时间')));
        $data->deadline     = $this->normalizeLarkDate($this->getLarkValue($payload, array('deadline', 'dueDate', '截止日期', '截止时间')));
        $data->completed    = $this->normalizeLarkBool($this->getLarkValue($payload, array('completed', 'done', 'isDone', '任务完成状态', '完成状态')));
        $data->finishedDate = $this->normalizeLarkDateTime($this->getLarkValue($payload, array('finishedDate', 'completedTime', '完成时间')));
        if($data->completed === false) $data->error = '飞书任务未完成，不同步。';
        if(!$data->finishedDate) $data->finishedDate = helper::now();
        if(!$data->deadline) $data->deadline = substr($data->finishedDate, 0, 10);
        $data->realStarted  = '';
        $data->estimate    = $this->normalizeLarkFloat($this->getLarkValue($payload, array('estimate', 'workhour', '工时', '预计工时', '工时 (/人时)')));
        if($data->estimate <= 0) $data->estimate = (float)zget($this->config->task->larkSync, 'defaultEstimate', 1);
        $data->pri         = $this->normalizeLarkPriority($this->getLarkValue($payload, array('pri', 'priority', '优先级')));
        $data->type        = (string)zget($this->config->task->larkSync, 'defaultType', 'devel');

        if($data->name == '') $data->error = '任务标题不能为空。';
        if(!$data->assignedTo) $data->error = '负责人无法映射到禅道账号。';
        if(!$data->execution) $data->error = '执行 ID 不能为空。';

        return $data;
    }

    /**
     * Get a value from payload by possible keys.
     *
     * @param  object    $payload
     * @param  array     $keys
     * @access private
     * @return mixed
     */
    private function getLarkValue(object $payload, array $keys): mixed
    {
        foreach($keys as $key)
        {
            if(isset($payload->{$key})) return $payload->{$key};
        }

        if(isset($payload->fields) && is_object($payload->fields))
        {
            foreach($keys as $key)
            {
                if(isset($payload->fields->{$key})) return $payload->fields->{$key};
            }
        }

        return null;
    }

    /**
     * Get a query parameter value by possible keys.
     *
     * @param  array     $keys
     * @access private
     * @return string
     */
    private function getLarkQueryValue(array $keys): string
    {
        foreach($keys as $key)
        {
            if(isset($_GET[$key])) return trim((string)$_GET[$key]);
        }

        $queryString = (string)zget($_SERVER, 'QUERY_STRING', '');
        if($queryString == '' && !empty($_SERVER['REQUEST_URI']))
        {
            $requestURI = (string)$_SERVER['REQUEST_URI'];
            $queryString = parse_url($requestURI, PHP_URL_QUERY) ?: '';
        }

        if($queryString != '')
        {
            $params = array();
            parse_str($queryString, $params);
            foreach($keys as $key)
            {
                if(isset($params[$key])) return trim((string)$params[$key]);
            }
        }

        return '';
    }

    /**
     * Resolve Lark assignee to ZenTao account.
     *
     * @param  mixed     $assignee
     * @access private
     * @return string
     */
    private function resolveLarkAssignee(mixed $assignee): string
    {
        $candidates = array();
        if(is_array($assignee))
        {
            $first = current($assignee);
            if(is_object($first)) $candidates = array($first->name ?? '', $first->realname ?? '', $first->en_name ?? '', $first->email ?? '');
            if(is_string($first)) $candidates = array($first);
        }
        elseif(is_object($assignee))
        {
            $candidates = array($assignee->name ?? '', $assignee->realname ?? '', $assignee->en_name ?? '', $assignee->email ?? '');
        }
        else
        {
            $candidates = array((string)$assignee);
        }

        foreach($candidates as $name)
        {
            $name = trim((string)$name);
            $nameList = preg_split('/[,，、]/u', $name);
            if(!empty($nameList)) $name = trim((string)current($nameList));
            if($name == '') continue;

            $user = $this->dao->select('account')->from(TABLE_USER)->where('deleted')->eq('0')->andWhere('account')->eq($name)->fetch();
            if($user) return $user->account;

            $users = $this->dao->select('account,realname,email')->from(TABLE_USER)->where('deleted')->eq('0')->andWhere('realname')->eq($name)->fetchAll();
            if(count($users) == 1) return $users[0]->account;

            $users = $this->dao->select('account,realname,email')->from(TABLE_USER)->where('deleted')->eq('0')->andWhere('email')->eq($name)->fetchAll();
            if(count($users) == 1) return $users[0]->account;
        }

        return '';
    }

    /**
     * Normalize task title from Lark.
     *
     * @param  mixed     $name
     * @access private
     * @return string
     */
    private function normalizeLarkTaskName(mixed $name): string
    {
        $name = trim((string)$name);
        $name = preg_replace('/\s+/u', ' ', $name);
        if(!is_string($name)) $name = '';

        return $this->truncateLarkTaskName($name);
    }

    /**
     * Truncate task title to ZenTao's task name limit.
     *
     * @param  string    $name
     * @param  int       $length
     * @access private
     * @return string
     */
    private function truncateLarkTaskName(string $name, int $length = 255): string
    {
        if($length <= 3) return substr($name, 0, $length);
        if(function_exists('mb_strlen') && mb_strlen($name, 'UTF-8') > $length) return mb_substr($name, 0, $length - 3, 'UTF-8') . '...';
        if(!function_exists('mb_strlen') && strlen($name) > $length) return substr($name, 0, $length - 3) . '...';

        return $name;
    }

    /**
     * Complete start/deadline dates before saving Lark task data.
     *
     * @param  object       $data
     * @param  object|null  $oldTask
     * @access private
     * @return void
     */
    private function completeLarkTaskDates(object $data, ?object $oldTask = null): void
    {
        $finishedDate = (string)zget($data, 'finishedDate', '');
        $finishedDay  = $finishedDate ? substr($finishedDate, 0, 10) : helper::today();

        if(!$data->deadline) $data->deadline = $finishedDay;

        if(!$data->estStarted)
        {
            if($oldTask && !helper::isZeroDate($oldTask->estStarted)) $data->estStarted = $oldTask->estStarted;
            if(!$data->estStarted || (!helper::isZeroDate($data->deadline) && $data->deadline < $data->estStarted)) $data->estStarted = $data->deadline ?: $finishedDay;
        }

        if(!$data->estStarted) $data->estStarted = $finishedDay;
        $data->realStarted = $data->estStarted . ' 00:00:00';

        if(!helper::isZeroDate($data->deadline) && !helper::isZeroDate($data->estStarted) && $data->deadline < $data->estStarted)
        {
            $data->error = sprintf('截止日期不能早于预计开始：预计开始 %s，截止日期 %s。', $data->estStarted, $data->deadline);
        }
    }

    /**
     * Apply normalized data to existing task.
     *
     * @param  object    $task
     * @param  object    $data
     * @param  object    $oldTask
     * @access private
     * @return void
     */
    private function applyLarkDataToTask(object $task, object $data, object $oldTask): void
    {
        $task->name       = $data->name;
        $task->desc       = $data->desc;
        $task->assignedTo = $data->assignedTo;
        $task->estStarted  = $data->estStarted;
        $task->realStarted = $data->realStarted;
        $task->deadline   = $data->deadline;
        $task->estimate   = $data->estimate;
        $task->pri        = $data->pri;
        $task->status     = 'done';
        $task->consumed   = max((float)$oldTask->consumed, $data->estimate);
        $task->left       = 0;

        if($task->assignedTo != $oldTask->assignedTo) $task->assignedDate = helper::now();
        if($oldTask->status != 'done')
        {
            $task->finishedBy   = $data->assignedTo ?: $this->app->user->account;
            $task->finishedDate = $data->finishedDate;
        }
        if($oldTask->name != $task->name || $oldTask->deadline != $task->deadline) $task->version = (int)$oldTask->version + 1;
    }

    /**
     * Remove expanded task fields that cannot be written back to zt_task.
     *
     * @param  object    $task
     * @access private
     * @return void
     */
    private function sanitizeLarkTaskForUpdate(object $task): void
    {
        $taskFields = array_flip(array(
            'id', 'project', 'parent', 'isParent', 'isTpl', 'path', 'execution', 'module',
            'design', 'story', 'storyVersion', 'designVersion', 'fromBug', 'fromIssue',
            'feedback', 'name', 'complexity', 'type', 'mode', 'color', 'pri', 'estimate',
            'consumed', 'left', 'deadline', 'status', 'subStatus', 'mailto', 'keywords',
            'desc', 'version', 'openedBy', 'openedDate', 'assignedTo', 'assignedDate',
            'estStarted', 'realStarted', 'finishedBy', 'finishedDate', 'finishedList',
            'canceledBy', 'canceledDate', 'closedBy', 'closedDate', 'realDuration',
            'planDuration', 'closedReason', 'lastEditedBy', 'lastEditedDate',
            'activatedDate', 'order', 'repo', 'mr', 'entry', 'lines', 'v1', 'v2',
            'deleted', 'vision'
        ));

        foreach(get_object_vars($task) as $field => $value)
        {
            if(!isset($taskFields[$field]) || is_array($value) || is_object($value)) unset($task->{$field});
        }
    }

    /**
     * Create ZenTao task from normalized Lark data.
     *
     * @param  object    $data
     * @param  object    $execution
     * @access private
     * @return int|false
     */
    private function createLarkTask(object $data, object $execution): int|false
    {
        $task = new stdclass();
        $task->project      = $execution->project;
        $task->execution    = $data->execution;
        $task->module       = 0;
        $task->story        = 0;
        $task->storyVersion = 1;
        $task->parent       = 0;
        $task->mode         = '';
        $task->color        = '';
        $task->name         = $data->name;
        $task->complexity   = 'L1';
        $task->type         = $data->type;
        $task->pri          = $data->pri;
        $task->estimate     = $data->estimate;
        $task->consumed     = $data->estimate;
        $task->left         = 0;
        $task->desc         = $data->desc;
        $task->estStarted   = $data->estStarted;
        $task->realStarted  = $data->realStarted;
        $task->deadline     = $data->deadline;
        $task->status       = 'done';
        $task->openedBy     = $this->app->user->account;
        $task->openedDate   = $data->openedDate ?: helper::now();
        $task->assignedTo   = $data->assignedTo;
        $task->assignedDate = $data->assignedTo ? helper::now() : null;
        $task->finishedBy   = $data->assignedTo ?: $this->app->user->account;
        $task->finishedDate = $data->finishedDate;
        $task->version      = 1;
        $task->vision       = $this->config->vision;
        $task->mailto       = '';
        $task->keywords     = '';

        $this->dao->begin();
        $taskID = $this->task->create($task);
        if(!$taskID || dao::isError())
        {
            $this->dao->rollBack();
            return false;
        }

        $task->id = $taskID;
        $this->task->afterCreate($task, array($taskID), 0, 0);
        if(dao::isError())
        {
            $this->dao->rollBack();
            return false;
        }
        $this->dao->commit();

        return (int)$taskID;
    }

    /**
     * Normalize priority.
     *
     * @param  mixed     $priority
     * @access private
     * @return int
     */
    private function normalizeLarkPriority(mixed $priority): int
    {
        if(is_array($priority)) $priority = current($priority);
        if(is_object($priority)) $priority = $priority->text ?? $priority->name ?? $priority->value ?? '';

        $priority = trim((string)$priority);
        if(is_numeric($priority)) return max(1, min(4, (int)$priority));

        $map = array('最高' => 1, '紧急' => 1, '急' => 1, 'P0' => 1, '高' => 1, 'A1' => 1, '中' => 2, '普通' => 2, 'P1' => 2, 'A2' => 2, '低' => 3, 'P2' => 3, 'A3' => 3);
        return zget($map, $priority, (int)$this->config->task->default->pri);
    }

    /**
     * Normalize float.
     *
     * @param  mixed     $value
     * @access private
     * @return float
     */
    private function normalizeLarkFloat(mixed $value): float
    {
        if(is_array($value)) $value = current($value);
        if(is_object($value)) $value = $value->value ?? $value->text ?? 0;
        return max(0, round((float)$value, 2));
    }

    /**
     * Normalize boolean values from Lark.
     *
     * @param  mixed     $value
     * @access private
     * @return bool|null
     */
    private function normalizeLarkBool(mixed $value): ?bool
    {
        if(is_array($value)) $value = current($value);
        if(is_object($value)) $value = $value->value ?? $value->text ?? $value->name ?? null;
        if($value === null || $value === '') return null;
        if(is_bool($value)) return $value;
        if(is_numeric($value)) return (int)$value === 1;

        $value = strtolower(trim((string)$value));
        if(in_array($value, array('true', 'yes', 'done', 'completed', '已完成', '完成'), true)) return true;
        if(in_array($value, array('false', 'no', 'wait', 'doing', '未完成', '未完成任务'), true)) return false;

        return null;
    }

    /**
     * Normalize date.
     *
     * @param  mixed     $value
     * @access private
     * @return string|null
     */
    private function normalizeLarkDate(mixed $value): ?string
    {
        $datetime = $this->normalizeLarkDateTime($value);
        return $datetime ? substr($datetime, 0, 10) : null;
    }

    /**
     * Normalize datetime.
     *
     * @param  mixed     $value
     * @access private
     * @return string|null
     */
    private function normalizeLarkDateTime(mixed $value): ?string
    {
        if(is_array($value)) $value = current($value);
        if(is_object($value)) $value = $value->timestamp ?? $value->value ?? $value->text ?? '';
        if($value === null || $value === '') return null;

        if(is_numeric($value))
        {
            $timestamp = (int)$value;
            if($timestamp > 9999999999) $timestamp = (int)floor($timestamp / 1000);
            return date('Y-m-d H:i:s', $timestamp);
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    /**
     * Build task link.
     *
     * @param  int       $taskID
     * @access private
     * @return string
     */
    private function buildTaskLink(int $taskID): string
    {
        return "https://pm.hexincorp.com/index.php?m=task&f=view&taskID={$taskID}";
    }

    /**
     * Send JSON response.
     *
     * @param  bool      $success
     * @param  mixed     $message
     * @param  array     $extra
     * @param  int       $status
     * @access private
     * @return void
     */
    private function sendLarkSyncResponse(bool $success, mixed $message, array $extra = array(), int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $errorText = $this->formatLarkSyncMessage($message);
        $response  = array(
            'success'   => $success,
            'message'   => $success || is_string($message) ? (string)$message : '保存失败',
            'errorText' => $errorText,
            'action'    => '',
            'taskID'    => 0,
            'taskLink'  => '',
            'syncTime'  => helper::now()
        );

        $response = array_merge($response, $extra);
        $this->saveLarkSyncLog($success, $status, $response);

        helper::end(json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Initialize Lark sync log context.
     *
     * @access private
     * @return void
     */
    private function initLarkSyncLog(): void
    {
        $this->larkSyncLog = array(
            'requestID'    => function_exists('uniqid') ? uniqid('lark_', true) : (string)mt_rand(),
            'requestTime'  => helper::now(),
            'method'       => (string)zget($_SERVER, 'REQUEST_METHOD', ''),
            'requestURI'   => $this->truncateLarkSyncLogValue((string)zget($_SERVER, 'REQUEST_URI', '')),
            'queryString'  => $this->truncateLarkSyncLogValue((string)zget($_SERVER, 'QUERY_STRING', '')),
            'remoteAddr'   => (string)zget($_SERVER, 'REMOTE_ADDR', ''),
            'userAgent'    => $this->truncateLarkSyncLogValue((string)zget($_SERVER, 'HTTP_USER_AGENT', '')),
            'contentType'  => '',
            'recordID'     => '',
            'larkTaskID'   => '',
            'runID'        => '',
            'zentaoTaskID' => 0,
            'taskName'     => '',
            'assignee'     => '',
            'assignedTo'   => '',
            'execution'    => 0,
            'estimate'     => 0,
            'completed'    => null,
            'estStarted'   => '',
            'deadline'     => '',
            'finishedDate' => '',
            'rawBody'      => '',
            'jsonError'    => ''
        );
    }

    /**
     * Append query params to Lark sync log.
     *
     * @access private
     * @return void
     */
    private function appendLarkSyncLogQueryParams(): void
    {
        $recordID = $this->getLarkQueryValue(array('recordID', 'record_id', 'recordId', '飞书记录ID'));
        $taskID   = $this->getLarkQueryValue(array('larkTaskID', 'taskGuid', 'feishuTaskID', '任务 ID', '任务ID'));
        $runID    = $this->getLarkQueryValue(array('runID', 'runId', 'automationRunID', 'logID', '日志ID', '飞书运行日志ID'));

        if($recordID != '') $this->larkSyncLog['recordID'] = $recordID;
        if($taskID != '')   $this->larkSyncLog['larkTaskID'] = $taskID;
        if($runID != '')    $this->larkSyncLog['runID'] = $runID;
    }

    /**
     * Append source payload fields to Lark sync log.
     *
     * @param  object    $payload
     * @access private
     * @return void
     */
    private function appendLarkSyncLogPayload(object $payload): void
    {
        $recordID = (string)$this->getLarkValue($payload, array('record_id', 'recordID', '飞书记录ID'));
        $taskID   = (string)$this->getLarkValue($payload, array('taskGuid', 'taskID', '任务 ID', '任务ID'));
        if($recordID != '') $this->larkSyncLog['recordID'] = $recordID;
        if($taskID != '')   $this->larkSyncLog['larkTaskID'] = $taskID;

        $assignee = $this->getLarkValue($payload, array('assignedTo', 'assignee', '负责人', '执行人', '飞书负责人'));
        if(is_array($assignee)) $assignee = current($assignee);
        if(is_object($assignee)) $assignee = $assignee->name ?? $assignee->realname ?? $assignee->en_name ?? $assignee->email ?? '';
        $this->larkSyncLog['assignee'] = $this->truncateLarkSyncLogValue((string)$assignee);
    }

    /**
     * Append normalized data to Lark sync log.
     *
     * @param  object    $data
     * @access private
     * @return void
     */
    private function appendLarkSyncLogData(object $data): void
    {
        $this->larkSyncLog['zentaoTaskID'] = (int)zget($data, 'taskID', 0);
        $this->larkSyncLog['taskName']     = $this->truncateLarkSyncLogValue((string)zget($data, 'name', ''));
        $this->larkSyncLog['assignedTo']   = (string)zget($data, 'assignedTo', '');
        $this->larkSyncLog['execution']    = (int)zget($data, 'execution', 0);
        $this->larkSyncLog['estimate']     = (float)zget($data, 'estimate', 0);
        $this->larkSyncLog['completed']    = zget($data, 'completed', null);
        $this->larkSyncLog['estStarted']   = (string)zget($data, 'estStarted', '');
        $this->larkSyncLog['deadline']     = (string)zget($data, 'deadline', '');
        $this->larkSyncLog['finishedDate'] = (string)zget($data, 'finishedDate', '');
    }

    /**
     * Save Lark sync log.
     *
     * @param  bool      $success
     * @param  int       $status
     * @param  array     $response
     * @access private
     * @return void
     */
    private function saveLarkSyncLog(bool $success, int $status, array $response): void
    {
        if(empty($this->larkSyncLog)) $this->initLarkSyncLog();

        $log = $this->larkSyncLog;
        $log['success']   = $success;
        $log['httpCode']  = $status;
        $log['action']    = (string)zget($response, 'action', '');
        $log['taskID']    = (int)zget($response, 'taskID', 0);
        $log['taskLink']  = (string)zget($response, 'taskLink', '');
        $log['message']   = $this->truncateLarkSyncLogValue((string)zget($response, 'message', ''));
        $log['errorText'] = $this->truncateLarkSyncLogValue((string)zget($response, 'errorText', ''));

        $logRoot = $this->app->getTmpRoot() . 'log' . DS;
        if(!is_dir($logRoot)) @mkdir($logRoot, 0755, true);

        $logFile = $logRoot . 'lark_sync.' . date('Ymd') . '.log.php';
        $content = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        if(!file_exists($logFile)) $content = "<?php\ndie();\n?" . ">\n" . $content;

        @file_put_contents($logFile, $content, FILE_APPEND);
    }

    /**
     * Truncate long log values.
     *
     * @param  string    $value
     * @param  int       $length
     * @access private
     * @return string
     */
    private function truncateLarkSyncLogValue(string $value, int $length = 2000): string
    {
        if(function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > $length) return mb_substr($value, 0, $length, 'UTF-8') . '...';
        if(!function_exists('mb_strlen') && strlen($value) > $length) return substr($value, 0, $length) . '...';
        return $value;
    }

    /**
     * Format response message for Lark text fields.
     *
     * @param  mixed     $message
     * @access private
     * @return string
     */
    private function formatLarkSyncMessage(mixed $message): string
    {
        if(is_string($message)) return $message;
        if(is_array($message))
        {
            $textList = array();
            foreach($message as $field => $errors)
            {
                $errors = is_array($errors) ? implode('；', $errors) : (string)$errors;
                $textList[] = "{$field}: {$errors}";
            }
            return implode('；', $textList);
        }

        return json_encode($message, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 批量编辑任务。
     * Batch edit tasks.
     *
     * @param  int    $executionID
     * @param  string $from
     * @access public
     * @return void
     */
    public function batchEdit(int $executionID = 0, string $from = '')
    {
        $this->taskZen->setMenu($executionID);

        if($this->post->name)
        {
            /* Batch edit tasks. */
            $taskData = form::batchData()->get();
            $oldTasks = $taskData ? $this->task->getByIdList(array_keys($taskData)) : array();
            $taskData = $this->taskZen->buildTasksForBatchEdit($taskData, $oldTasks);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $allChanges = $this->task->batchUpdate($taskData);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $this->task->afterBatchUpdate($taskData, $oldTasks);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $response = $this->taskZen->responseAfterBatchEdit($allChanges);
            return $this->send($response);
        }

        if(!$this->post->taskIdList)
        {
            $url = !empty($this->session->taskList) ? $this->session->taskList : $this->createLink('execution', 'all');
            $this->locate($url);
        }

        if($this->app->tab == 'my' && $this->config->vision == 'rnd')
        {
            $this->loadModel('my');
            if($from == 'work' || $from == 'contribute')
            {
                $this->lang->my->menu->{$from}['subModule'] = 'task';
                $this->lang->my->menu->{$from}['subMenu']->task['subModule'] = 'task';
            }
        }

        $this->taskZen->assignBatchEditVars($executionID);
    }

    /**
     * 指派任务。
     * Update assign of task.
     *
     * @param  int    $executionID
     * @param  int    $taskID
     * @param  string $from        ''|taskkanban
     * @access public
     * @return void
     */
    public function assignTo(int $executionID, int $taskID, string $from = '')
    {
        $this->taskZen->commonAction($taskID);

        $task = $this->task->getByID($taskID);
        if(!empty($task->team) && $task->mode == 'multi' && strpos('done,cancel,closed', $task->status) === false)
        {
            echo $this->fetch('task', 'manageTeam', "executionID=$executionID&taskID=$taskID&from=$from");
            return;
        }

        if(!empty($_POST))
        {
            $task = form::data($this->config->task->form->assign, $taskID)->add('id', $taskID)->get();

            $this->task->assign($task);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $message = $this->executeHooks($taskID);
            $message = $message ?: $this->lang->saveSuccess;

            $response = $this->taskZen->responseAfterAssignTo($taskID, $from, $message);
            return $this->send($response);
        }

        $this->view->task  = $task;
        $this->view->title = $this->view->execution->name . $this->lang->hyphen . $this->lang->task->assign;
        $this->taskZen->buildUsersAndMembersToForm($executionID, $taskID);
        $this->display();
    }

    /**
     * 批量更改任务所属模块。
     * Batch change the module of task.
     *
     * @param  int    $moduleID
     * @access public
     * @return void
     */
    public function batchChangeModule(int $moduleID)
    {
        if($this->post->taskIdList)
        {
            $taskIdList = array_unique($this->post->taskIdList);
            $this->task->batchChangeModule($taskIdList, $moduleID);

            if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchOther');
        }

        if(dao::isError()) return array('result' => 'fail', 'message' => dao::getError());

        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'load' => true));
    }

    /**
     * 批量指派任务。
     * Batch update assign of task.
     *
     * @param  int    $executionID
     * @param  string $assignedTo
     * @access public
     * @return void
     */
    public function batchAssignTo(int $executionID, string $assignedTo)
    {
        if($this->post->taskIdList)
        {
            $taskData = $this->taskZen->buildTasksForBatchAssignTo($this->post->taskIdList, $assignedTo);
            foreach($taskData as $task)
            {
                $this->task->assign($task);
                if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            }

            if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchOther');
        }
        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'load' => $this->createLink('execution', 'task', "executionID={$executionID}")));
    }

    /**
     * 查看一个任务。View a task.
     *
     * @param  int    $taskID
     * @access public
     * @return void
     */
    public function view(int $taskID)
    {
        $task = $this->task->getById($taskID, true, $vision = 'all'); // TODO: $vision is for compatibling with viewing drill data.
        if(!$task)
        {
            if(defined('RUN_MODE') && RUN_MODE == 'api') return $this->send(array('status' => 'fail', 'code' => 404, 'message' => '404 Not found'));
            return $this->sendError($this->lang->notFound, $this->config->vision == 'lite' ? $this->createLink('project', 'index') : $this->createLink('execution', 'all'));
        }
        if(!$this->loadModel('common')->checkPrivByObject('execution', $task->execution)) return $this->sendError($this->lang->execution->accessDenied, $this->createLink('execution', 'all'));

        /* 为视图设置常用的公共变量和设置菜单为任务所属执行. Set common variables to view and set menu to the execution of the task. */
        $this->session->project = $task->project;
        $this->taskZen->commonAction($taskID, $vision = 'all');

        $this->session->set('executionList', $this->app->getURI(true), 'execution'); // This allow get var of session as `$_SESSION['app-execution']['executionList']`.

        $execution    = $this->view->execution ?? $this->execution->getById($task->execution);
        $isModalOrApi = helper::isAjaxRequest('modal') || (defined('RUN_MODE') && RUN_MODE == 'api');
        if(!$isModalOrApi && $execution->type == 'kanban')
        {
            helper::setcookie('taskToOpen', (string)$taskID);
            return $this->send(array('load' => $this->createLink('execution', 'kanban', "executionID=$execution->id")));
        }

        /* 检查和设置任务的相关信息如果它来自缺陷或需求。Check and set related info if the task came from bug or story. */
        if($task->fromBug != 0)
        {
            $bug = $this->loadModel('bug')->getById($task->fromBug);
            $task->bugSteps = '';
            if($bug)
            {
                $task->bugSteps = $this->loadModel('file')->setImgSize($bug->steps);
                foreach($bug->files as $file) $task->files[] = $file;
            }
            $this->view->fromBug = $bug;
            if($this->app->tab == 'qa') $this->view->productID = $bug->product;
        }
        else
        {
            $story = $this->story->getById($task->story, $task->storyVersion);
            $task->storySpec   = empty($story) ? '' : $this->loadModel('file')->setImgSize($story->spec);
            $task->storyVerify = empty($story) ? '' : $this->loadModel('file')->setImgSize($story->verify);
            $task->storyFiles  = zget($story, 'files', array());
            $task->storyTitle  = !empty($story) ? $story->title : '';
        }

        if($task->team) $this->lang->task->assign = $this->lang->task->transfer;
        $this->lang->task->statusList['changed'] = $this->lang->task->storyChange;

        $this->view->title        = "TASK#$task->id $task->name / $execution->name";
        $this->view->execution    = $execution;
        $this->view->task         = $task;
        $this->view->actions      = $this->loadModel('action')->getList('task', $taskID);
        $this->view->users        = $this->loadModel('user')->getPairs('noletter');
        $this->view->preAndNext   = $this->loadModel('common')->getPreAndNextObject('task', $taskID);
        $this->view->product      = $this->tree->getProduct($task->module);
        $this->view->modulePath   = $this->tree->getParents($task->module);
        $this->view->linkMRTitles = $this->loadModel('mr')->getLinkedMRPairs($taskID, 'task');
        $this->view->linkCommits  = $this->loadModel('repo')->getCommitsByObject($taskID, 'task');
        $this->view->linkedBugs   = $this->loadModel('bug')->getLinkedBugsByTaskID($taskID);
        $this->view->hasGitRepo   = $this->taskZen->checkGitRepo($execution->id);
        $this->display();
    }

    /**
     * 确认需求变更。
     * Confirm story change.
     *
     * @param  int    $taskID
     * @access public
     * @return void
     */
    public function confirmStoryChange(int $taskID)
    {
        $this->task->confirmStoryChange($taskID);
        $message = $this->executeHooks($taskID);

        return $this->send(array('result' => 'success', 'message' => $message ?: $this->lang->saveSuccess, 'load' => true));
    }

    /**
     * 开始一个任务。
     * Start a task.
     *
     * @param  int    $taskID
     * @param  string $cardPosition
     * @access public
     * @return void
     */
    public function start(int $taskID, string $cardPosition = '')
    {
        /* Analytic parameter. */
        $cardPosition = str_replace(array(',', ' '), array('&', ''), $cardPosition);
        parse_str($cardPosition, $output);

        /* Common actions of task module and task. */
        $this->taskZen->commonAction($taskID);
        $task        = $this->task->getById($taskID);
        $currentTeam = !empty($task->team) ? $this->task->getTeamByAccount($task->team) : '';

        /* Submit the data process after start the task form. */
        if(!empty($_POST))
        {
            /* Prepare the data information before start the task. */
            $taskData = $this->taskZen->buildTaskForStart($task);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            /* Record task effort. */
            $effort = $this->buildEffortForStart($task, $taskData);
            if($this->post->comment) $effort->work = $this->post->comment;
            if($effort->consumed > 0) $effortID = $this->task->addTaskEffort($effort);
            if($task->mode == 'linear' && !empty($effortID)) $this->task->updateEffortOrder($effortID, $currentTeam->order);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            /* Start a task. */
            $changes = $this->task->start($task, $taskData);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $message = $this->executeHooks($task->id);
            $message = $message ?: $this->lang->saveSuccess;

            /* Update other data related to the task after it is started. */
            $result = $this->task->afterStart($task, $changes, (float)$this->post->left, $output, $message);
            if(is_array($result)) $this->send($result);

            /* Get the information returned after a task is started. */
            $from     = zget($output, 'from');
            $response = $this->taskZen->responseAfterChangeStatus($task, $from, $message);
            return $this->send($response);
        }

        /* Shows the variables needed to start the task page. */
        $assignedTo = empty($task->assignedTo) ? $this->app->user->account : $task->assignedTo;

        $this->view->title           = $this->view->execution->name . $this->lang->hyphen .$this->lang->task->start;
        $this->view->assignedTo      = !empty($task->team) ? $this->task->getAssignedTo4Multi($task->team, $task) : $assignedTo;
        $this->view->canRecordEffort = $this->task->canOperateEffort($task);
        $this->view->currentTeam     = $currentTeam;
        $this->taskZen->buildUsersAndMembersToForm($task->execution, $taskID);
        $this->display();
    }

    /**
     * 任务查看工时/新增工时的方法。
     * View and add task's workhour.
     *
     * @param  int    $taskID
     * @param  string $from
     * @param  string $orderBy
     * @access public
     * @return void
     */
    public function recordWorkhour(int $taskID, string $from = '', string $orderBy = '')
    {
        $this->taskZen->commonAction($taskID);

        if(!empty($_POST))
        {
            $workhour = form::batchData($this->config->task->form->recordWorkhour)->get();
            $changes  = $this->task->recordWorkhour($taskID, $workhour);
            if(dao::isError()) return $this->send(array('message' => dao::getError(), 'result' => 'fail'));

            /* 更新任务对应的项目集、项目和执行的状态。*/
            $this->loadModel('common')->syncPPEStatus($taskID);

            $task     = $this->task->getById($taskID);
            $response = $this->taskZen->responseAfterRecord($task, $changes, $from);
            return $this->send($response);
        }

        $this->taskZen->buildRecordForm($taskID, $from, $orderBy);
    }

    /**
     * 编辑一条日志。
     * Edit a effort.
     *
     * @param  int    $effortID
     * @access public
     * @return void
     */
    public function editEffort(int $effortID)
    {
        $effort = $this->task->getEffortByID($effortID);
        if(!empty($_POST))
        {
            if($this->config->edition != 'open') $oldEffort = $this->loadModel('effort')->fetchByID($effortID);
            $this->lang->task->consumed = $this->lang->task->currentConsumed;
            $formData = form::data($this->config->task->form->editEffort)->add('id', $effortID)->get();
            $changes  = $this->task->updateEffort($formData);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $actionID = $this->loadModel('action')->create('task', $effort->objectID, 'EditEffort', $this->post->work);
            $this->action->logHistory($actionID, $changes);

            if($this->config->edition != 'open')
            {
                $effort   = $this->effort->fetchByID($effortID);
                $actionID = $this->action->create('effort', $effortID, 'edited');
                $this->action->logHistory($actionID, common::createChanges($oldEffort, $effort));
            }

            $url = $this->createLink($this->app->rawModule, 'recordWorkhour', "taskID={$effort->objectID}");
            return $this->send(array(
                'result'     => 'success',
                'message'    => $this->lang->saveSuccess,
                'callback'   => "loadModal('$url')"
            ));
        }

        $this->view->title  = $this->lang->task->editEffort;
        $this->view->effort = $effort;
        $this->view->task   = $this->task->getByID($effort->objectID);
        $this->display();
    }

    /**
     * 删除任务工时。Delete the work hour from the task.
     *
     * @param  int    $effortID
     * @param  string $confirm
     * @access public
     * @return void
     */
    public function deleteWorkhour(int $effortID, string $confirm = 'no')
    {
        $effort = $this->task->getEffortByID($effortID);
        $taskID = $effort->objectID;
        $task   = $this->task->getById($taskID);

        /* Show a confirm message if the task has no consumed effort. */
        if($confirm == 'no' && $task->consumed - $effort->consumed == 0)
        {
            $formUrl = $this->createLink('task', 'deleteWorkhour', "effortID=$effortID&confirm=yes");
            return $this->send(array('result' => 'fail', 'callback' => "zui.Modal.confirm('{$this->lang->task->confirmDeleteLastEffort}').then((res) => {if(res) $.ajaxSubmit({url: '$formUrl'});});"));
        }

        $changes = $this->task->deleteWorkhour($effortID);
        if(dao::isError() || empty($changes)) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

        $actionID = $this->loadModel('action')->create('task', $taskID, 'DeleteEstimate');
        $this->action->logHistory($actionID, $changes);

        /* Delete task burn. */
        if($this->config->edition != 'open')
        {
            $this->dao->update(TABLE_BURN)
                 ->set("`consumed` = `consumed` - {$effort->consumed}")
                 ->where('task')->eq($taskID)
                 ->andWhere('date')->eq($effort->date)
                 ->exec();
        }

        /* The task status will be set to wait as the consumed effort is set to 0. */
        if($task->consumed - $effort->consumed == 0) $this->action->create('task', $taskID, 'Adjusttasktowait');

        return $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'callback' => "loadModal('" . inLink('recordworkhour', "taskID={$taskID}") . "', '#modal-record-hours-task-{$taskID}')"));
    }

    /**
     * 完成任务操作。
     * Finish a task.
     *
     * @param  int    $taskID
     * @param  string $extra
     * @access public
     * @return void
     */
    public function finish(int $taskID, string $extra = '')
    {
        $extra = str_replace(array(',', ' '), array('&', ''), $extra);
        parse_str($extra, $output);

        $this->taskZen->commonAction($taskID);
        $task        = $this->view->task;
        $currentTeam = !empty($task->team) ? $this->task->getTeamByAccount($task->team) : '';

        $canRecordEffort = $this->task->canOperateEffort($task);
        if(!$canRecordEffort)
        {
            $deniedNotice = sprintf($this->lang->task->deniedNotice, $this->lang->task->teamMember, $this->lang->task->finish);
            if($task->assignedTo != $this->app->user->account && $task->mode == 'linear') $deniedNotice = sprintf($this->lang->task->deniedNotice, $task->assignedToRealName, $this->lang->task->finish);

            $this->view->deniedNotice = $deniedNotice;
        }

        if(!empty($_POST))
        {
            if(!$canRecordEffort) return $this->send(array('result' => 'fail', 'message' => $deniedNotice));

            $taskData = $this->taskZen->buildTaskForFinish($task);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            if($task->mode == 'linear' && $taskData->finishedBy != $task->assignedTo) return $this->send(array('result' => 'fail', 'message' => $this->lang->task->error->finishedBy));

            /* Get and record estimate for task. */
            $effort = $this->taskZen->buildEffortForFinish($task, $taskData);
            if($effort->consumed > 0) $effortID = $this->task->addTaskEffort($effort);
            if($task->mode == 'linear' && !empty($effortID)) $this->task->updateEffortOrder($effortID, isset($currentTeam->order) ? $currentTeam->order : 1);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $changes = $this->task->finish($task, $taskData);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $message = $this->executeHooks($taskID);
            $message = $message ?: $this->lang->saveSuccess;

            /* Update other data related to the task after it is started. */
            $result = $this->task->afterStart($task, $changes, 0, $output, $message);
            if(is_array($result)) return $this->send($result);

            if($taskData->status == 'done') $this->loadModel('score')->create('task', 'finish', $task->id);
            if($this->config->edition != 'open' && $task->feedback) $this->loadModel('feedback')->updateStatus('task', $task->feedback, $taskData->status, $task->status, $task->id);

            /* Get the information returned after a task is started. */
            $from     = zget($output, 'from');
            $response = $this->taskZen->responseAfterChangeStatus($task, $from, $message);
            return $this->send($response);
        }

        $task         = $this->view->task;
        $task->nextBy = $task->openedBy;

        if(!empty($task->team))
        {
            $task->nextBy     = $this->task->getAssignedTo4Multi($task->team, $task, 'next');
            $task->myConsumed = zget($currentTeam, 'consumed', 0);
        }

        $this->taskZen->buildUsersAndMembersToForm($task->execution, $taskID);

        $this->view->title           = $this->view->execution->name . $this->lang->hyphen .$this->lang->task->finish;
        $this->view->canRecordEffort = $canRecordEffort;
        $this->display();
    }

    /**
     * 暂停任务。
     * Pause task.
     *
     * @param  int    $taskID
     * @param  string $extra
     * @access public
     * @return void
     */
    public function pause(int $taskID, string $extra = '')
    {
        $this->taskZen->commonAction($taskID);

        $extra = str_replace(array(',', ' '), array('&', ''), $extra);
        parse_str($extra, $output);

        if(!empty($_POST))
        {
            $oldTask = $this->task->getByID($taskID);

            /* Init task data. */
            $task = form::data($this->config->task->form->pause, $taskID)->add('id', $taskID)->get();

            /* Pause task. */
            $changes = $this->task->pause($task, $output);
            if(dao::isError()) return array('result' => 'fail', 'message' => dao::getError());

            /* Record log. */
            if($this->post->comment != '' || !empty($changes))
            {
                $actionID = $this->loadModel('action')->create('task', $taskID, 'Paused', $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }

            $message = $this->executeHooks($taskID);
            $message = $message ?: $this->lang->saveSuccess;

            /* Get response after the suspended task. */
            $from     = zget($output, 'from');
            $response = $this->taskZen->responseAfterChangeStatus($oldTask, $from, $message);
            return $this->send($response);
        }

        /* Show the variables associated. */
        $this->view->title = $this->view->execution->name . $this->lang->hyphen .$this->lang->task->pause;
        $this->view->users = $this->loadModel('user')->getPairs('noletter');
        $this->display();
    }

    /**
     * 重新开始一个任务。
     * Restart a task.
     *
     * @param  int    $taskID
     * @param  string $from
     * @access public
     * @return void
     */
    public function restart(int $taskID, string $from = '')
    {
        /* Common actions of task module and task. */
        $this->taskZen->commonAction($taskID);
        $task        = $this->task->getById($taskID);
        $account     = $this->app->user->account;
        $currentTeam = !empty($task->team) ? $this->task->getTeamByAccount($task->team, $account, array()) : '';

        if(!empty($_POST))
        {
            /* Prepare the data information before restart the task. */
            $taskData = $this->taskZen->buildTaskForStart($task);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            /* Record task effort. */
            $effort = $this->taskZen->buildEffortForStart($task, $taskData);
            if($this->post->comment) $effort->work = $this->post->comment;
            if($effort->consumed > 0) $effortID = $this->task->addTaskEffort($effort);
            if($task->mode == 'linear' && !empty($effortID)) $this->task->updateEffortOrder($effortID, $currentTeam->order);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            /* Restart a task. */
            $changes = $this->task->start($task, $taskData);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $message  = $this->executeHooks($taskID);
            $message  = $message ?: $this->lang->saveSuccess;

            $result = $this->task->afterStart($task, $changes, (float)$this->post->left, array(), $message);
            if(is_array($result)) $this->send($result);

            $response = $this->taskZen->responseAfterChangeStatus($task, $from, $message);
            return $this->send($response);
        }

        $this->view->title           = $this->view->execution->name . $this->lang->hyphen .$this->lang->task->restart;
        $this->view->users           = $this->loadModel('user')->getPairs('noletter');
        $this->view->members         = $this->loadModel('user')->getTeamMemberPairs($task->execution, 'execution', 'nodeleted');
        $this->view->assignedTo      = $task->assignedTo == '' ? $this->app->user->account : $task->assignedTo;
        $this->view->canRecordEffort = $this->task->canOperateEffort($task);
        $this->view->currentTeam     = $currentTeam;
        $this->display();
    }

    /**
     * 关闭一个任务。
     * Close a task.
     *
     * @param  int    $taskID
     * @param  string $cardPosition
     * @access public
     * @return void
     */
    public function close(int $taskID, string $cardPosition = '')
    {
        $cardPosition = str_replace(array(',', ' '), array('&', ''), $cardPosition);
        parse_str($cardPosition, $output);

        $this->taskZen->commonAction($taskID);

        if(!empty($_POST))
        {
            $task = $this->task->getById($taskID);

            /* Prepare the data information before start the task. */
            $taskData = $this->taskZen->buildTaskForClose($task);
            $result   = $this->task->close($task, $taskData, $output);
            if(!$result) return $this->send(array('result' => 'fail', 'message' => dao::getError()));
            if(is_array($result)) return $this->send($result);

            $message = $this->executeHooks($taskID);
            $message = $message ?: $this->lang->saveSuccess;

            /* Get the information returned after a task is started. */
            $from     = zget($output, 'from');
            $response = $this->taskZen->responseAfterChangeStatus($task, $from, $message);
            return $this->send($response);
        }

        $this->view->title = $this->view->execution->name . $this->lang->hyphen .$this->lang->task->finish;
        $this->view->users = $this->loadModel('user')->getPairs('noletter');
        $this->display();
    }

    /**
     * 批量取消任务。
     * Batch cancel tasks.
     *
     * @access public
     * @return void
     */
    public function batchCancel()
    {
        $taskIdList = $this->post->taskIdList ? array_unique($this->post->taskIdList) : array();
        if($taskIdList) $taskIdList = array_filter(array_unique($taskIdList));
        if($taskIdList)
        {
            foreach($taskIdList as $taskID)
            {
                $task = $this->task->fetchById((int)$taskID);
                if(!in_array($task->status, $this->config->task->unfinishedStatus)) continue;

                $taskData = $this->taskZen->buildTaskForCancel($task);
                $this->task->cancel($task, $taskData);
            }
        }

        return $this->send(array('result' => 'success', 'load' => true));
    }

    /**
     * 批量关闭任务。
     * Batch close tasks.
     *
     * @param  string $confirm yes|no
     * @access public
     * @return void
     */
    public function batchClose(string $confirm = 'no')
    {
        $skipTasks  = array();
        $taskIdList = $this->post->taskIdList ? array_unique($this->post->taskIdList) : array();
        $taskList   = $this->task->getByIdList($taskIdList);

        if($confirm == 'no')
        {
            foreach($taskList as $taskID => $task)
            {
                if(!in_array($task->status, array('done', 'cancel')))
                {
                    $skipTasks[$taskID] = $taskID;
                }
            }

            if(!empty($skipTasks))
            {
                $skipTasks    = implode(',', $skipTasks);
                $url          = $this->createLink('task', 'batchClose', "confirm=yes");
                $confirm      = sprintf($this->lang->task->error->skipClose, $skipTasks);

                $data['url']  = $url;
                $data['data'] = array('taskIdList[]' => $this->post->taskIdList);
                $data         = json_encode($data);
                return $this->send(array('result' => 'fail', 'callback' => "zui.Modal.confirm('{$confirm}').then((res) => {if(res) $.ajaxSubmit($data);});"));
            }
        }

        foreach($taskList as $taskID => $task)
        {
            if($task->status == 'closed') continue;
            if($task->parent > 0 && isset($taskList[$task->parent])) continue; // 同时选中父子，关闭父的时候就关闭子了，不用重复关闭子

            $taskData = $this->taskZen->buildTaskForClose($task);
            $this->task->close($task, $taskData);
        }

        if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchOther');

        return $this->send(array('result' => 'success', 'load' => true));
    }

    /**
     * 取消一个任务。
     * Cancel a task.
     *
     * @param  int    $taskID
     * @param  string $cardPosition
     * @param  string $from
     * @access public
     * @return void
     */
    public function cancel(int $taskID, string $cardPosition = '', string $from = '')
    {
        $this->taskZen->commonAction($taskID);

        $cardPosition = str_replace(array(',', ' '), array('&', ''), $cardPosition);
        parse_str($cardPosition, $output);

        if(!empty($_POST))
        {
            $oldTask = $this->task->getByID($taskID);
            $task    = $this->taskZen->buildTaskForCancel($oldTask);
            $result  = $this->task->cancel($oldTask, $task, $output);
            if(!$result) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $message = $this->executeHooks($taskID);

            if(helper::isAjaxRequest('modal')) return $this->send($this->taskZen->responseModal($oldTask, $from, $message));
            return $this->send(array('result' => 'success', 'message' => $message ?: $this->lang->saveSuccess, 'closeModal' => true, 'load' => $this->createLink('task', 'view', "taskID=$taskID")));
        }

        $this->view->title = $this->view->execution->name . $this->lang->hyphen . $this->lang->task->cancel;
        $this->view->users = $this->loadModel('user')->getPairs('noletter');

        $this->display();
    }

    /**
     * 激活一个任务
     * Activate a task.
     *
     * @param  int    $taskID
     * @param  string $cardPosition
     * @param  string $from
     * @access public
     * @return void
     */
    public function activate(int $taskID, string $cardPosition = '', string $from = '')
    {
        /* Analytic parameter. */
        $cardPosition = str_replace(array(',', ' '), array('&', ''), $cardPosition);
        parse_str($cardPosition, $output);

        $this->taskZen->commonAction($taskID);

        if(!empty($_POST))
        {
            /* Prepare the data information before activate the task. */
            $task = $this->taskZen->buildTaskForActivate($taskID);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $teamData = form::data($this->config->task->form->team->edit)->get();
            $this->task->activate($task, (string)$this->post->comment, $teamData, $output);
            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $message = $this->executeHooks($taskID);
            $message = $message ?: $this->lang->saveSuccess;

            /* Get the information returned after a task is started. */
            $task     = $this->task->fetchByID($taskID);
            $response = $this->taskZen->responseAfterChangeStatus($task, $from, $message);
            return $this->send($response);
        }

        if(!isset($this->view->members[$this->view->task->finishedBy])) $this->view->members[$this->view->task->finishedBy] = $this->view->task->finishedBy; // Ensure that the completion person is on the user list.

        /* Get task teammembers. */
        if(!empty($this->view->task->team))
        {
            $teamAccounts = array_column($this->view->task->team, 'account');
            $teamMembers  = array();
            foreach($this->view->members as $account => $name)
            {
                if(!$account || in_array($account, $teamAccounts)) $teamMembers[$account] = $name;
            }
            $this->view->teamMembers = $teamMembers;
        }

        $this->view->title      = $this->view->execution->name . $this->lang->hyphen . $this->lang->task->activate;
        $this->view->users      = $this->loadModel('user')->getPairs('noletter');
        $this->view->isMultiple = !empty($this->view->task->team);
        $this->display();
    }

    /**
     * 删除一个任务。
     * Delete a task.
     *
     * @param  int    $taskID
     * @param  string $from
     * @access public
     * @return void
     */
    public function delete(int $taskID, string $from = '')
    {
        $task = $this->task->fetchByID($taskID);

        /* 如果是父任务，先删除所有子任务 */
        if($task->isParent)
        {
            dao::$filterTpl = 'never';

            $childIdList = $this->task->getAllChildId($taskID, false);
            $childTasks  = $this->task->getByIdList($childIdList);
            foreach($childTasks as $childID => $childTask)
            {
                if(strpos(",{$childTask->path},", ",$taskID,") === false) continue;
                $this->task->delete(TABLE_TASK, $childID);
                if($childTask->fromBug != 0) $this->dao->update(TABLE_BUG)->set('toTask')->eq(0)->where('id')->eq($childTask->fromBug)->exec();
                if($childTask->story) $this->loadModel('story')->setStage($childTask->story);
            }
        }

        /* 删除当前任务 */
        $this->task->delete(TABLE_TASK, $taskID);
        if($task->parent > 0)
        {
            $this->task->updateParentStatus($task->id);
            $this->loadModel('action')->create('task', $task->parent, 'deleteChildrenTask', '', $taskID);
        }
        if($task->fromBug != 0) $this->dao->update(TABLE_BUG)->set('toTask')->eq(0)->where('id')->eq($task->fromBug)->exec();
        if($task->story) $this->loadModel('story')->setStage($task->story);

        $message = $this->executeHooks($taskID);
        $message = $message ?: $this->lang->saveSuccess;

        /* 在看板中删除任务时的返回。*/
        /* Respond when delete in kanban. */
        if($from == 'taskkanban') return $this->send(array('result' => 'success', 'closeModal' => true, 'callback' => "refreshKanban()"));

        if($from == 'view') return $this->send(array('result' => 'success', 'message' => $message, 'closeModal' => true, 'load' => true));

        $link = $this->session->taskList ? $this->session->taskList : $this->createLink('execution', 'task', "executionID={$task->execution}");
        $link = isInModal() ? true : $link;
        return $this->send(array('result' => 'success', 'message' => $message, 'load' => $link, 'closeModal' => true));
    }

    /**
     * AJAX: return tasks of a user in html select.
     *
     * @param  int    $userID
     * @param  string $id
     * @param  string $status
     * @param  int    $appendID
     * @access public
     * @return string
     */
    public function ajaxGetUserTasks(int $userID = 0, string $id = '', string $status = 'wait,doing', int $appendID = 0)
    {
        if($userID == 0) $userID = $this->app->user->id;
        $user    = $this->loadModel('user')->getById($userID, 'id');
        $account = $user->account;

        $tasks          = $this->task->getUserTaskPairs($account, $status, array(), array($appendID));
        $suspendedTasks = $this->task->getUserSuspendedTasks($account);
        $items          = array();
        foreach($tasks as $taskID => $taskName)
        {
            if(isset($suspendedTasks[$taskID]))
            {
                unset($tasks[$taskID]);
                continue;
            }
            $items[] = array('text' => $taskName, 'value' => $taskID);
        }

        $fieldName = $id ? "tasks[$id]" : 'task';
        return print(json_encode(array('name' => $fieldName, 'items' => $items)));
    }

    /**
     * AJAX: return execution tasks in html select.
     *
     * @param  int    $executionID
     * @access public
     * @return string
     */
    public function ajaxGetExecutionTasks(int $executionID)
    {
        $tasks = $this->task->getExecutionTaskPairs((int)$executionID);

        $items = array();
        foreach($tasks as $taskID => $taskName) $items[] = array('text' => $taskName, 'value' => $taskID, 'keys' => $taskName);
        return print(json_encode($items));
    }

    /**
     * 禅道客户端获取任务动态。
     * AJAX: get the actions of a task. for web app.
     *
     * @param  int    $taskID
     * @access public
     * @return void
     */
    public function ajaxGetDetail(int $taskID)
    {
        $this->view->actions = $this->loadModel('action')->getList('task', $taskID);
        $this->display();
    }

    /**
     * The report page.
     *
     * @param  int    $executionID
     * @param  string $browseType
     * @param  string $chartType  default|pie|bar|line
     * @access public
     * @return void
     */
    public function report(int $executionID, string $browseType = 'all', string $chartType = 'default')
    {
        $this->loadModel('report');
        $this->view->charts = array();
        $this->execution->setMenu($executionID);

        /* Build chart data. */
        $chartList = array();
        $this->view->datas = array();
        if(!empty($_POST)) $chartList = $this->taskZen->buildChartData($chartType);

        /* If the project is non-execution, the chart of tasks by execution is not shown. */
        $execution = $this->loadModel('execution')->getByID($executionID);
        if(!$execution->multiple) unset($this->lang->task->report->charts['tasksPerExecution']);

        if($this->app->tab == 'project') $this->view->projectID = $execution->project;

        $this->view->title         = $execution->name . $this->lang->hyphen . $this->lang->task->report->common;
        $this->view->executionID   = $executionID;
        $this->view->execution     = $execution;
        $this->view->browseType    = $browseType;
        $this->view->chartType     = $chartType;
        $this->view->charts        = $chartList;
        $this->view->checkedCharts = $this->post->charts ? implode(',', $this->post->charts) : '';

        $this->display();
    }

    /**
     * 导出任务数据。
     * get data to export.
     *
     * @param  int    $executionID
     * @param  string $orderBy
     * @param  string $type     browse type, such as: all,unclosed,assignedtome,myinvolved,assignedbyme,needconfirm,etc
     * @access public
     * @return void
     */
    public function export(int $executionID, string $orderBy, string $type)
    {
        /* Get execution info and export fields. */
        $execution       = $this->execution->getByID($executionID);
        $allExportFields = $this->config->task->exportFields;
        if($execution->lifetime == 'ops' || in_array($execution->attribute, array('request', 'review'))) $allExportFields = str_replace(' story,', '', $allExportFields);

        if($_POST)
        {
            $this->loadModel('file');
            if($type == 'bysearch') $this->config->task->dtable->fieldList['module']['dataSource'] = array('module' => 'tree', 'method' => 'getAllModulePairs');

            /* Create field lists. */
            $fields = $this->taskZen->getExportFields($allExportFields);

            /* Get tasks. */
            $tasks = $this->task->getExportTasks($orderBy);
            if($type == 'group') $tasks = $this->taskZen->processExportGroup($executionID, $tasks, $orderBy);

            /* Process export data. */
            $tasks = $this->taskZen->processExportData($tasks, $execution->project);
            if($this->config->edition != 'open') list($fields, $tasks) = $this->loadModel('workflowfield')->appendDataFromFlow($fields, $tasks);

            $this->post->set('fields', $fields);
            $this->post->set('rows', $tasks);
            $this->post->set('kind', 'task');
            $this->fetch('file', 'export2' . $this->post->fileType, $_POST);
        }

        if(isset($this->lang->execution->featureBar['task'][$type]))
        {
            $browseType = $this->lang->execution->featureBar['task'][$type];
        }
        else
        {
            $browseType = isset($this->lang->execution->statusSelects[$type]) ? $this->lang->execution->statusSelects[$type] : '';
        }

        $this->view->fileName        = $execution->name . $this->lang->dash . $browseType . $this->lang->task->common;
        $this->view->allExportFields = $allExportFields;
        $this->view->customExport    = true;
        $this->view->orderBy         = $orderBy;
        $this->view->type            = $type;
        $this->view->executionID     = $executionID;
        $this->view->execution       = $execution;

        $this->display();
    }

    /**
     * AJAX: Get the json of the task by ID.
     * Note: This function is NOT used in open edition.
     *
     * @param  int    $taskID
     * @access public
     * @return void
     */
    public function ajaxGetByID(int $taskID)
    {
        $task = $this->dao->select('*')->from(TABLE_TASK)->where('id')->eq($taskID)->fetch();
        if(!$task) return;

        $realname   = $this->dao->select('realname')->from(TABLE_USER)->where('account')->eq($task->assignedTo)->fetch('realname');
        $assignedTo = $task->assignedTo == 'closed' ? 'Closed' : $task->assignedTo;

        $task->assignedTo = $realname ? $realname : $assignedTo;
        if($task->story)
        {
            $this->app->loadLang('story');
            $stage = $this->dao->select('stage')->from(TABLE_STORY)->where('id')->eq($task->story)->andWhere('version')->eq($task->storyVersion)->fetch('stage');
            $task->storyStage = zget($this->lang->story->stageList, $stage);
        }
        return print(json_encode($task));
    }

    /**
     * 管理多人任务的团队。
     * Update assign of multi task.
     *
     * @param  int    $executionID
     * @param  int    $taskID
     * @param  string $from        ''|taskkanban
     * @access public
     * @return void
     */
    public function manageTeam(int $executionID, int $taskID, string $from = '')
    {
        $this->taskZen->commonAction($taskID);

        if(!empty($_POST))
        {
            /* Update assign of multi task. */
            $postData = form::data($this->config->task->form->manageTeam);
            $task     = $this->taskZen->prepareManageTeam($postData, $taskID);

            $postData->team         = $this->post->team;
            $postData->teamEstimate = $this->post->teamEstimate;
            $postData->teamConsumed = $this->post->teamConsumed;
            $postData->teamLeft     = $this->post->teamLeft;
            $postData->teamSource   = $this->post->teamSource;
            $changes = $this->task->updateTeam($task, $postData);

            if(dao::isError()) return $this->send(array('result' => 'fail', 'message' => dao::getError()));

            /* Record log. */
            $actionID = $this->loadModel('action')->create('task', $taskID, 'Edited');
            $this->action->logHistory($actionID, $changes);

            $message = $this->executeHooks($taskID);
            $message = $message ?: $this->lang->saveSuccess;

            $response = $this->taskZen->responseAfterAssignTo($taskID, $from, $message);
            return $this->send($response);
        }

        if($this->app->rawMethod == 'assignto')
        {
            if($this->view->execution->multiple)  $manageLink = common::hasPriv('execution', 'manageMembers') ? $this->createLink('execution', 'manageMembers', "execution={$this->view->execution->id}") : '';
            if(!$this->view->execution->multiple) $manageLink = common::hasPriv('project', 'manageMembers') ? $this->createLink('project', 'manageMembers', "projectID={$this->view->execution->project}") : '';
            $this->view->manageLink = $manageLink;
        }

        $this->view->members = $this->loadModel('user')->getTeamMemberPairs($executionID, 'execution', 'nodeleted');
        $this->view->users   = $this->loadModel('user')->getPairs();
        $this->display();
    }

    /**
     * AJAX: 返回一个html格式的需求选择列表用于创建测试类型的任务。该需求的名称将作为创建任务的子任务名称。
     * AJAX: Return the stories selection list in html format for create the task with test type. The child task has same name with the story.
     *
     * @param  int    $executionID
     * @param  int    $taskID      The task ID which the task copy from. This will be used for creating a task from a existed task.
     * @access public
     * @return string
     */
    public function ajaxGetTestStories(int $executionID, int $taskID = 0)
    {
        $stories         = $this->story->getExecutionStoryPairs($executionID, 0, 'all', '', 'ignoreID', 'active', 'story', false);
        $testStoryIdList = $this->story->getTestStories(array_keys($stories), $executionID);
        $testStories     = array();
        foreach($stories as $testStoryID => $storyTitle)
        {
            /**
             * 如果在执行中已经为该需求创建了测试类型任务，则跳过这个需求不再给前台展示。
             * If a test type task has already been created for the requirement during execution, the requirement is skipped and not shown to the foreground.
             */
            if(empty($testStoryID) || isset($testStoryIdList[$testStoryID])) continue;

            $testStories[$testStoryID] = $storyTitle;
        }

        $execution = $this->loadModel('execution')->fetchByID($executionID);
        if($execution->multiple)  $manageLink = common::hasPriv('execution', 'manageMembers') ? $this->createLink('execution', 'manageMembers', "execution={$execution->id}") : '';
        if(!$execution->multiple) $manageLink = common::hasPriv('project', 'manageMembers') ? $this->createLink('project', 'manageMembers', "projectID={$execution->project}") : '';

        $this->view->testStories = $testStories;
        $this->view->task        = $this->loadModel('task')->getByID($taskID);
        $this->view->members     = $this->loadModel('user')->getTeamMemberPairs($executionID, 'execution', 'nodeleted');
        $this->view->manageLink  = $manageLink;
        $this->display();
    }

    /**
     * AJAX: 获取批量创建任务的需求下拉列表。
     * AJAX: Get the stories selection list for batch create tasks.
     *
     * @param  int    $executionID
     * @param  int    $moduleID
     * @param  string $zeroTaskStory
     * @access public
     * @return void
     */
    public function ajaxGetStories(int $executionID, int $moduleID, string $zeroTaskStory = 'false')
    {
        $moduleType    = $moduleID ? $this->task->fetchByID($moduleID, 'module')->type : '';
        $stories       = $this->loadModel('story')->getExecutionStoryPairs($executionID, 0, 'all', $moduleType == 'task' ? 0 : $moduleID, 'full', 'active', 'story', false);
        $taskCountList = $this->task->getStoryTaskCounts(array_keys($stories), $executionID);

        $items = array();
        foreach($stories as $storyID => $storyName)
        {
            if(empty($storyName)) continue;
            if($zeroTaskStory == 'true' && $taskCountList[$storyID] > 0) continue;
            $items[] = array('text' => $storyName, 'value' => $storyID, 'keys' => $storyName);
        }

        return print(json_encode($items));
    }

    /**
     * AJAX: 获取任务的开始时间和截止时间。
     * AJAX: Get the start time and end time of the task.
     *
     * @param  int    $taskID
     * @access public
     * @return void
     */
    public function ajaxGetTaskEstStartedAndDeadline(int $taskID)
    {
        $task = $this->task->fetchById($taskID);
        $overParentEstStartedLang = !empty($task) ? sprintf($this->lang->task->overParentEsStarted, $task->estStarted) : '';
        $overParentDeadlineLang   = !empty($task) ? sprintf($this->lang->task->overParentDeadline, $task->deadline) : '';

        return print(json_encode(array('estStarted' => $task->estStarted, 'deadline' => $task->deadline, 'overParentEstStartedLang' => $overParentEstStartedLang, 'overParentDeadlineLang' => $overParentDeadlineLang)));
    }

    /**
     * 创建代码分支。
     * Create repo branch.
     *
     * @param  int    $taskID
     * @param  int    $repoID
     * @access public
     * @return void
     */
    public function createBranch(int $taskID, int $repoID = 0)
    {
        return print($this->fetch('repo', 'createBranch', array('objectID' => $taskID, 'repoID' => $repoID)));
    }

    /**
     * 取消代码分支的关联。
     * Unlink code branch.
     *
     * @access public
     * @return void
     */
    public function unlinkBranch()
    {
        return print($this->fetch('repo', 'unlinkBranch'));
    }
}
