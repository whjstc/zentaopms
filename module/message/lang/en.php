<?php
$lang->message->common     = 'Notification';
$lang->message->index      = 'Homepage';
$lang->message->setting    = 'Settings';
$lang->message->browser    = 'System Notification';
$lang->message->blockUser  = 'Blocked Users';
$lang->message->markUnread = 'Mark as Unread';

$lang->message->typeList['mail']     = 'Email';
$lang->message->typeList['message']  = 'System Notification';
$lang->message->typeList['webhook']  = 'Webhook';

$lang->message->browserSetting = new stdclass();
$lang->message->browserSetting->turnon   = 'Notification';
$lang->message->browserSetting->pollTime = 'Polling Interval';

$lang->message->browserSetting->pollTimeTip         = 'Polling interval cannot be less than 30 seconds.';
$lang->message->browserSetting->pollTimePlaceholder = 'Notification interval (in seconds).';

$lang->message->browserSetting->turnonList[1] = 'On';
$lang->message->browserSetting->turnonList[0] = 'Off';

$lang->message->browserSetting->more    = 'More Settings';
$lang->message->browserSetting->show    = 'Browser Notification';
$lang->message->browserSetting->count   = 'Notification Count';
$lang->message->browserSetting->maxDays = 'Retention Days';

$lang->message->unread = 'Unread Messages(%s)';
$lang->message->all    = 'All Messages';

$lang->message->timeLabel['minute'] = '%s minutes ago';
$lang->message->timeLabel['hour']   = '1 hour ago';

$lang->message->notice = new stdclass();
$lang->message->notice->allMarkRead = 'Mark All as Read';
$lang->message->notice->clearRead   = 'Clear read';

$lang->message->error = new stdclass();
$lang->message->error->maxDaysFormat  = 'Retention days must be a positive integer.';
$lang->message->error->maxDaysValue   = 'Retention Days cannot be less than 0.';

$lang->message->label = new stdclass();
$lang->message->label->created      = 'create';
$lang->message->label->opened       = 'Create';
$lang->message->label->changed      = 'change';
$lang->message->label->releaseddoc  = 'release';
$lang->message->label->edited       = 'edit';
$lang->message->label->assigned     = 'assign';
$lang->message->label->closed       = 'close';
$lang->message->label->deleted      = 'delete';
$lang->message->label->undeleted    = 'restore';
$lang->message->label->commented    = 'comment';
$lang->message->label->activated    = 'activate';
$lang->message->label->resolved     = 'resolve';
$lang->message->label->submitreview = 'Submit Review';
$lang->message->label->reviewed     = 'review';
$lang->message->label->confirmed    = "Confirm {$lang->SRCommon}";
$lang->message->label->frombug      = "Convert to {$lang->SRCommon}";
$lang->message->label->started      = 'start';
$lang->message->label->delayed      = 'delay';
$lang->message->label->suspended    = 'On Hold';
$lang->message->label->finished     = 'Complete';
$lang->message->label->paused       = 'pause';
$lang->message->label->canceled     = 'Cancle';
$lang->message->label->restarted    = 'continue';
$lang->message->label->blocked      = 'block';
$lang->message->label->bugconfirmed = 'confirm';
$lang->message->label->compilepass  = 'Build Passed';
$lang->message->label->compilefail  = 'Build Failed';
$lang->message->label->archived     = 'archive';
$lang->message->label->restore      = 'restore';
$lang->message->label->moved        = 'move';
$lang->message->label->published    = 'Release';
$lang->message->label->changestatus = 'Change Release Status';
