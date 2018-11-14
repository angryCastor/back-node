<?php
namespace Resume;

use Illuminate\Database\Capsule\Manager as Capsule;

class DbProvider{

    /**
     * Пользователь
     *
     * @var string
     */
    protected $user = "root";

    /**
     * Пароль
     *
     * @var string
     */
    protected $password = "";

    /**
     * База данных
     *
     * @var string
     */
    protected $database = "resume";

    /**
     * Хост
     *
     * @var string
     */
    protected $host = "localhost:3307";

    /**
     * Database manager
     *
     * @var Capsule
     */
    protected $capsule;

    private static $instance = null;


    private function __construct(){
        // $this->user = $user;
        // $this->password = $password;
        // $this->database = $database;
        // $this->host = $host;

        $this->Init();
        $this->CheckTables();
    }


    /**
     * Синглтон
     *
     * @return void
     */
    public static function Instance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new DbProvider();
        }
        return $instance;
    }


    /**
     * Инициализация соединения
     *
     * @return void
     */
    protected function Init(){
        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $this->host,
            'database'  => $this->database,
            'username'  => $this->user,
            'password'  => $this->password,
        ]);

        $this->capsule->setAsGlobal();
    }


    /**
     * Проверка на существование таблица,
     * Так же из создание, при неудаче
     *
     * @return void
     */
    protected function CheckTables(){
        if(!Capsule::schema()->hasTable("node") || !Capsule::schema()->hasTable("element")){
            Capsule::schema()->dropAllTables();
            Capsule::schema()->create("node", function($table){
                $table->increments('id');
                $table->integer('parent')->default(0);
                $table->string('name')->default("node");
            });
    
            Capsule::table('node')->insert(array(
                'name' => 'parent'
            ));
    
            Capsule::schema()->create("element", function($table){
                $table->increments('id');
                $table->integer('parent');
                $table->string('name')->default("element");
                $table->bigInteger('date')->default(946684800);
            });
        }
    }


    public function GetTree(){
        $result = [];
        $rows = Capsule::table("node")->select()->orderBy("parent")->get();
        $items = $rows->all();

        $correct = array();
        foreach ($items as $item){
            $correct[$item->parent][] = $item;
        }

        $tree = $this->CreateTreeBranch($correct, $correct[0]);

        return $tree[0];
    }
    
    public function GetElementsByParentId(int $id) : array{
        $rows = Capsule::table("element")->select()->where("parent", $id)->get();
        $items = $rows->all();
        return $items;
    }


    public function RemoveElement(int $id){
        Capsule::table("element")->where("id", $id)->delete();
    }

    
    public function CreateElement(string $name, int $date, int $parentId) : int{
        $id = Capsule::table("element")->insertGetId(array(
            "name" => $name,
            "date" => $date,
            "parent" => $parentId
        ));

        return $id;
    }


    public function EditElement(int $id, ?string $name = null, ?int $date = null, ?int $parent = null){
        $data = array();
        if($name !== null){
            $data["name"] = $name;
        }
        if($date !== null){
            $data["date"] = $date;
        }
        if($parent !== null){
            $data["parent"] = $parent;
        }
        Capsule::table("element")->where("id", $id)->update($data);
    }



    public function MoveNode(int $parentId, int $id){
        Capsule::table("node")->where("id", $id)->update(array("parent" => $parentId));
    }


    public function RenameNode(string $name, int $id){
        Capsule::table("node")->where("id", $id)->update(array("name" => $name));
    }


    public function RemoveNode(int $id){
        $rows = Capsule::table("node")->select()->orderBy("parent")->get();
        $items = $rows->all();
        $removeIds = $this->GetIdsChild($id, $items);
        Capsule::table("node")->where("id", $removeIds)->delete();
        Capsule::table("element")->whereIn("parent", $removeIds)->delete();
    }


    public function CreateNode(string $name, int $parentId) : int{
        return Capsule::table("node")->insertGetId(array(
            "parent" => $parentId,
            "name" => $name
        ));
    }

    private function GetIdsChild($id, &$nodes){
        $result = array();
        $result[] = $id;
        foreach($nodes as $node){
            if($node->parent === $id){
                
                $result = array_merge($result, $this->GetIdsChild($node->id, $nodes));
            }
        }

        return $result;
    }


    private function CreateTreeBranch(array &$items, $parents){
        $treeBranch = array();
        foreach($parents as $key => $parentItem){
            if(isset($items[$parentItem->id])){
                $parentItem->sections = $this->CreateTreeBranch($items, $items[$parentItem->id]);
            }
            else{
                $parentItem->sections = array();
            }
            $parentItem->items = array();
            $treeBranch[] = $parentItem;
        }

        return $treeBranch;
    }
}