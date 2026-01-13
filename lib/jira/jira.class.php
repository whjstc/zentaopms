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
    public function getProjects(string $url = '', string $account = '', string $password = ''): array
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

        $issueTypes = json_decode($result, true);
        if(!$issueTypes) return array();

        $typeList = array();
        foreach($issueTypes as $issueType) $typeList[$issueType['id']] = $issueType;

        return $typeList;
    }

    /**
     * 获取Jira中所有的Issue链接类型。
     *
     * @return array
     */
    public function getIssueLinkTypes()
    {
        $url      = $this->jiraDomain . '/rest/api/2/issueLinkType';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        $linkTypes = json_decode($result, true);
        if(!$linkTypes) return array();

        return $linkTypes['issueLinkTypes'];
    }

    /**
     * 获取Jira中指定项目的Issue。
     *
     * @param int     $startAt     开始位置
     * @param int     $maxResults  最大返回数量
     * @return string
     */
    public function getIssues($startAt = 0, $maxResults = 50)
    {
        $url      = $this->jiraDomain . '/rest/api/2/search';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $jql        = 'order by created desc';
        $url       .= '?jql=' . urlencode($jql) . "&startAt=" . $startAt . '&maxResults=' . $maxResults;
        $result     = common::http($url, null, array(), $header, 'data', 'GET');
        $result     = json_decode($result, true);
        $issues     = $result['issues'];

        foreach($issues as $index => $issue)
        {
            $issues[$index]['issuetype'] = $issue['fields']['issuetype']['id'];
        }

        return $issues;
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

        return json_decode($result, true);
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

        return json_decode($result, true);
    }

    /**
     * 获取Jira中的解决方式。
     *
     * @return array
     */
    public function getResolutions()
    {
        $url      = $this->jiraDomain . '/rest/api/2/resolution';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        return json_decode($result, true);
    }

    /**
     * 获取Jira中的工作流。
     *
     * @return array
     */
    public function getWorkflows()
    {
        $url      = $this->jiraDomain . '/rest/workflowDesigner/latest/workflows?name=WORKFLOW_NAME';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');

        return json_decode($result, true);
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

        return json_decode($result, true);
    }

    /**
     * 获取Jira中的自定义字段。
     *
     * @return array
     */
    public function getCustomFields()
    {
        $url      = $this->jiraDomain . '/rest/api/2/customFields';
        $account  = $this->jiraAccount;
        $password = $this->jiraToken;

        $authHeader = base64_encode($account . ':' . $password);
        $header     = array('Authorization: Basic ' . $authHeader);
        $result     = common::http($url, null, array(), $header, 'data', 'GET');
        $result     = json_decode($result, true);

        if(!isset($result['values'])) return array();
        $fields = $result['values'];
        $fields = array_filter($fields, function($field)
        {
            return $field['isLocked'] != 1; // 只返回用户自定义的字段
        });

        return $fields;
    }
}