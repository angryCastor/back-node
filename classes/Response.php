<?php
namespace Resume;
use Resume\DbProvider;

class Response{
    protected $params;
    protected $answer;
    protected $bodyResult;

    public function __construct($params){
        $this->params = $params;

        $this->Route();
    }

    public function Get() : string{
        $result["result"] = "ok";
        $result["body"] = $this->bodyResult;
        return json_encode($result);
    }

    protected function Route(){
        switch($this->params->action){
            case "get_tree": 
                $this->Tree();
                break;
            case "move_node":
                $this->MoveNode($this->params->pid, $this->params->id);
                break;
            case "remove_node":
                $this->RemoveNode($this->params->id);
                break;
            case "add_none":
                $this->AddNode($this->params->name, $this->params->pid);
                break;
            case "rename_node":
                $this->RenameNode($this->params->name, $this->params->id);
                break;
            case "get_elements":
                $this->Elements($this->params->id);
                break;
            case "add_element":
                $this->AddElement($this->params->name, $this->params->date, $this->params->pid);
                break;
            case "remove_element":
                $this->RemoveElement($this->params->id);
                break;
            case "edit_element":
                $name = null;
                $parent = null;
                $date = null;
                if(isset($this->params->name)){
                    $name = $this->params->name;
                }
                if(isset($this->params->pid)){
                    $parent = $this->params->pid;
                }
                if(isset($this->params->date)){
                    $date = $this->params->date;
                }
                $this->EditElement($this->params->id, $name, $parent, $date);
                break;
        }
    }

    protected function Tree(){
        $res = DbProvider::Instance()->GetTree();
        $data = array(
            "section" => $res
        );

        $this->bodyResult = $data;
    }

    protected function MoveNode(int $pid, int $id){
        $res = DbProvider::Instance()->MoveNode($pid, $id);
    }


    protected function RemoveNode(int $id){
        $res = DbProvider::Instance()->RemoveNode($id);
    }


    protected function AddNode(string $name, int $pid){
        $id = DbProvider::Instance()->CreateNode($name, $pid);
        $this->bodyResult["id"] = $id; 
    }

    protected function RenameNode(string $name, int $id){
        DbProvider::Instance()->RenameNode($name, $id);
    }

    protected function Elements(int $id){
        $items = DbProvider::Instance()->GetElementsByParentId($id);
        $this->bodyResult["items"] = $items;
    }

    protected function AddElement(string $name, int $date, int $pid){
        $id = DbProvider::Instance()->CreateElement($name, $date, $pid);
        $this->bodyResult["id"] = $id;
    }

    protected function RemoveElement(int $id){
        DbProvider::Instance()->RemoveElement($id);
    }

    protected function EditElement(int $id, ?string $name  = null, ?int $pid = null, ?int $date  = null){
        DbProvider::Instance()->EditElement($id, $name, $date, $pid);
    }
    
}