<?php
namespace EggCup;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);    
}

class Autoloader
{
    
    private static $namespace = "EggCup";

    private static $instance;
    
    private $mNamespaceLength;
    
    private $mDir;
    
    private function __construct()
    {
        $this->mNamespaceLength = strlen(self::$namespace);
        $this->mDir = dirname(__FILE__) . DS;
    }
    
    public static function GetInstance() 
    {
        if (self::$instance == null) 
        {
            self::$instance = new Autoloader();
        }
        return self::$instance;
    }
    
    public function Load($className)
    {
        if (substr($className, 0, $this->mNamespaceLength) !== self::$namespace)
        {
            return;
        }
        
        $className = substr($className, $this->mNamespaceLength);
        
        $fileName = str_replace('\\', DS, $className) . '.php';
        
        $filePath = $this->mDir . $fileName;
        
        require_once($filePath);
    }
    
}

spl_autoload_register(array(Autoloader::GetInstance(), 'Load'));
