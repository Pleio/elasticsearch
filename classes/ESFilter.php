<?php

class ESFilter {

    public function filter($object) {
        switch ($object->type) {
            case 'annotation':
                return $this->filterAnnotation($object);
            case 'object':
                return $this->filterObject($object);
            case 'user':
                return $this->filterUser($object);
            case 'site':
                return $this->filterSite($object);
            case default:
                return $this->filterOther($object);
        }
    }

    public function filterAnnotation($object) {
        if (in_array($object->name, array('groupforumtopic'))) {
            return $object;
        } else {
            return false;
        }
    }

    public function filterObject($object) {
        if ($object->subtype == 'plugin') {
            return false;
        } else {
            return $object;
        }
    }

    public function filterUser($object) {
        return $object;
    }

    public function filterSite($object) {
        return $object;
    }

    public function filterOther($object) {
        return $object;
    }

}