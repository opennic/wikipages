<?php

/**
 *  test wrapper to allow access to private/protected functions/properties
 *
 *  NB: for plugin introspection methods, getPluginType() & getPluginName() to work
 *      this class name needs to start "admin_" and end "_usermanager".  Internally
 *      these methods are used in setting up the class, e.g. for language strings
 */
class admin_mock_usermanager extends admin_plugin_usermanager {

    public $mock_email_notifications = true;
    public $mock_email_notifications_sent = 0;

    public $localised;
    public $lang;

    public function getImportFailures() {
        return $this->import_failures;
    }

    public function tryExport() {
        ob_start();
        $this->exportCSV();
        return ob_get_clean();
    }

    public function tryImport() {
        return $this->importCSV();
    }

    // no need to send email notifications (mostly)
    protected function notifyUser($user, $password, $status_alert=true) {
        if ($this->mock_email_notifications) {
            $this->mock_email_notifications_sent++;
            return true;
        } else {
            return parent::notifyUser($user, $password, $status_alert);
        }
    }

    protected function isUploadedFile($file) {
        return file_exists($file);
    }
}

class auth_mock_authplain extends auth_plugin_authplain {

    public function setCanDo($op, $canDo) {
        $this->cando[$op] = $canDo;
    }

}
