<?php

namespace Slim\Example;

class UserRepository 
{
    private $path = __DIR__ . '/users.txt';

    public function all() {
        $data = json_decode(file_get_contents($this->path), true);
        return $data;
    }
    
    public function save($users) {
        file_put_contents($this->path, json_encode($users));
    }

    public function find($id) {
        $data = $this->all();
        if(array_key_exists($id, $data)){
            return $data[$id];
        } 
    }

    public function remove($id) {
        $all = $this->all();
        unset($all[$id]);
        $this->save($all);
    }

}
