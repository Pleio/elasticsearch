<?php
class ESSchedule {
    private static $instance;

    public static function get() {
        if (null === static::$instance) {
            static::$instance = new ESSchedule();
        }

        return static::$instance;
    }

    protected function __construct() {
        $this->tasks = array(
            "update" => array(),
            "delete" => array()
        );
    }

    public function schedule($task = "update", $object) {
        $guid = ($object->guid ? $object->guid : $object->type . ":" . $object->id);
        if (!$guid) {
            return;
        }

        if (!in_array($task, array("update", "delete"))) {
            throw new Exception("Invalid task type. Use update or delete.");
        }

        $this->tasks[$task][$guid] = $object;
    }

    public function execute() {
        foreach ($this->tasks["update"] as $object) {
            ESInterface::get()->update($object);
        }

        foreach ($this->tasks["delete"] as $object) {
            ESInterface::get()->delete($object);
        }
    }
}