<?php
class jira
{
    /**
     * Jira接口的域名。
     * Jira api domain.
     *
     * @var string
     * @access public
     */
    public $jiraDomain;

    /**
     * Jira接口的用户名。
     * Jira api account.
     *
     * @var string
     * @access public
     */
    public $jiraAccount;

    /**
     * Jira接口的Token。
     * Jira api token.
     *
     * @var string
     * @access public
     */
    public $jiraToken;

    /**
     * 构造方法。
     * The construct function.
     *
     * @param  string $jiraDomain
     * @param  string $jiraAccount
     * @param  string $jiraToken
     * @access public
     * @return void
     */
    public function __construct(string $jiraDomain, string $jiraAccount, string $jiraToken)
    {
        $this->jiraDomain  = $jiraDomain;
        $this->jiraAccount = $jiraAccount;
        $this->jiraToken   = $jiraToken;
    }

    /**
     * 获取Jira中所有项目。
     *
     * @param string $url      Jira地址
     * @param string $account  Jira账号
     * @param string $password Jira密码
     * @return array
     */
    public function getProjectList(string $url = '', string $account = '', string $password = ''): array
    {
        $url      = $url ? $url : $this->jiraDomain;
        $account  = $account ? $account : $this->jiraAccount;
        $password = $password ? $password : $this->jiraToken;

        $url .= '/rest/api/2/project';
        $header = array('Authorization: Basic ' . base64_encode($account . ':' . $password));
        $result = common::http($url, null, array(), $header, 'data', 'GET');

        $projects = json_decode($result);
        if(!$projects) return array();

        return $projects;
    }

    /**
     * 获取Jira中所有的Issue类型。
     *
     * @return array
     */
    public function getIssueTypes(): array
    {
        $url      = $this->jiraDomain . '/rest/api/2/issuetype/';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        $issueTypes = json_decode($result);
        if(!$issueTypes) return array();

        $pairs = array();
        foreach ($issueTypes as $issueType) $pairs[$issueType->id] = $issueType->name;

        return $pairs;
    }

    /**
     * 获取Jira中指定项目的Issue。
     *
     * @param int     $projectID   项目ID
     * @param int     $issueTypeID Issue类型ID
     * @param int     $startAt     开始位置
     * @param int     $maxResults  最大返回数量
     * @return string
     */
    public function getIssues($projectID, $issueTypeID, $startAt = 0, $maxResults = 50)
    {
        $url      = $this->jiraDomain . '/rest/api/2/search';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $jql        = 'project = ' . $projectID . ' AND issuetype = ' . $issueTypeID . ' AND status != closed';
        $fields     = 'id,summary,description,timeestimate,priority,status,creator,created,assignee,issuelinks';
        if(isset($this->config->jira->storyPoint)) $fields .= ',' . $this->config->jira->storyPoint;
        $url       .= '?jql=' . urlencode($jql) . "&fields=$fields&startAt=" . $startAt . '&maxResults=' . $maxResults;
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        return json_decode($result);
    }

    /**
     * 获取Jira中的用户。
     *
     * @param  int $startAt    开始位置
     * @param  int $maxResults 最大返回数量
     * @return string
     */
    public function getUsers($startAt = 0, $maxResults = 50)
    {
        $url      = $this->jiraDomain . '/rest/api/2/user/search?username=.&startAt=' . $startAt . '&maxResults=' . $maxResults;
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        return json_decode($result);
    }

    /**
     * 获取Jira中指定项目的Board。
     *
     * @param  int    $projectID 项目ID
     * @return string
     */
    public function getBoardId($projectID)
    {
        $url      = $this->jiraDomain . '/rest/agile/1.0/board?projectKeyOrId=' . $projectID;
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        return json_decode($result);
    }

    /**
     * 获取Jira中的Epic.
     *
     * @return string
     */
    public function getIssuesByEpic($epicID, $startAt = 0, $maxResults = 50)
    {
        $url      = $this->jiraDomain . "/rest/agile/1.0/epic/$epicID/issue";
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $url .= '?fields=id&startAt=' . $startAt . '&maxResults=' . $maxResults;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        return json_decode($result);
    }

    /**
     * 将迭代同步到Jira中。
     *
     * @param  object  $sprint 迭代信息
     * @return string
     */
    public function createSprint($sprint)
    {
        $url      = $this->jiraDomain . '/rest/agile/1.0/sprint';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, $sprint, array(), $header, 'json', 'POST');

        return json_decode($result);
    }

    /**
     * 将Issue移动到指定迭代中。
     *
     * @param  int    $sprintID 迭代ID
     * @param  array  $issues   Issue列表
     * @return string
     */
    public function moveIssuesToSprint($sprintID, $issues)
    {
        $url      = $this->jiraDomain . '/rest/agile/1.0/sprint/' . $sprintID . '/issue';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, $issues, array(), $header, 'json', 'POST');

        return json_decode($result);
    }

    /**
     * 编辑Jira中的Issue。
     *
     * @param  int    $issueID IssueID
     * @param  object $issue
     * @return string
     */
    public function editIssue($issueID, $issue)
    {
        $this->app->loadLang('story');
        $storyPointField = isset($this->config->jira->storyPoint) ? $this->config->jira->storyPoint : '';
        $body = array();
        $body['update']['summary']      = array(array('set' => $issue->title));
        $body['update']['description']  = array(array('set' => strip_tags($issue->spec)));
        if(isset($issue->estimate) and $storyPointField) $body['update'][$storyPointField] = array(array('set' => (int)$issue->estimate));
        if(isset($issue->pri)) $body['update']['priority'] = array(array('set' => array('id' => (string)$issue->pri, 'name' => $this->lang->story->priList[$issue->pri])));

        $url      = $this->jiraDomain . '/rest/api/2/issue/' . $issueID;
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, $body, array(), $header, 'json', 'PUT');

        return json_decode($result);
    }

    /**
     * 指派Issue给指定用户。
     *
     * @param  int    $issueID  IssueID
     * @param  string $assignee 用户名
     * @return string
     */
    public function assignIssue($issueID, $assignee)
    {
        $body = $assignee ? array('name' => $assignee) : array('name' => null);

        $url      = $this->jiraDomain . '/rest/api/2/issue/' . $issueID . '/assignee';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, $body, array(), $header, 'json', 'PUT');

        return json_decode($result);
    }

    /**
     * 获取Jira中所有的优先级。
     *
     * @return array
     */
    public function getPriority()
    {
        $url      = $this->jiraDomain . '/rest/api/2/priority';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        return json_decode($result);
    }

    /**
     * 获取Jira中所有的状态。
     *
     * @return array
     */
    public function getStatus()
    {
        $url      = $this->jiraDomain . '/rest/api/2/status';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        return json_decode($result);
    }

    /**
     * 导入Jira中的优先级。
     * Import priority from Jira.
     *
     * @param  int $boardID BoardID
     * @return array
     */
    public function importPri($priList)
    {
        if(!$priList) return;
        $this->loadModel('custom');
        foreach($priList as $pri)
        {
            $this->custom->setItem("all.story.priList.{$pri->id}", $pri->name);
        }
    }
}
