<?php

/*
 * 功能：安装模块
 * Author:资料空白
 * Date:20180626
 */
use WriteiniFile\WriteiniFile;
class SetptwoController extends BasicController
{
	private $install_sql = APP_PATH.'/application/modules/Install/files/faka.sql';
	private $install_config = APP_PATH.'/conf/application.ini';
	
	public function init()
    {
        parent::init();
    }

    public function indexAction()
    {
		if(file_exists(INSTALL_LOCK)){
			$this->redirect("/product/");
			return FALSE;
		}else{
			$data = array();
			$this->getView()->assign($data);
		}
    }
	
	public function ajaxAction()
	{
		$host = $this->getPost('host',false);
		$port = $this->getPost('port',false);
		$user = $this->getPost('user', false);
		$password = $this->getPost('password', false);
		$dbname = $this->getPost('dbname', false);
		
		$data = array();
		
		if($host AND $port AND $user AND $password){
            try {
				if(file_exists($this->install_sql) AND is_readable($this->install_sql)){
					$sql = @file_get_contents($this->install_sql);
					if(!$sql){
						$data = array('code' => 1002, 'msg' =>"无法读取".$this->install_sql."文件,请检查文件是否存在且有读权限");
						Helper::response($data);
					}
				}else{
					$data = array('code' => 1003, 'msg' =>"无法读取".$this->install_sql."文件,请检查文件是否存在且有读权限");
					Helper::response($data);
				}
				
				if (!is_writable($this->install_config)){
					$data = array('code' => 1004, 'msg' =>"无法写入".$this->install_config."文件,请检查是否有写权限");
					Helper::response($data);
				}
				
				if (!is_writable(INSTALL_PATH)){
					$data = array('code' => 1005, 'msg' =>"无法写入目录".INSTALL_PATH.",请检查是否有写权限");
					Helper::response($data);
				}
				
                $pdo = new PDO("mysql:host=".$host.";port=".$port.";charset=utf8;",$user, $password, array(PDO::ATTR_PERSISTENT => true,PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
				$isexists = $pdo->query("show databases like '{$dbname}'");
				if($isexists->rowCount()>0){
					$data = array('code' => 1006, 'msg' =>"该数据库已存在");
				}else{
					$pdo->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8 COLLATE utf8_general_ci;");
					$pdo->query("USE `{$dbname}`");
					$pdo->exec($sql);
					
					$ini = new WriteiniFile($this->install_config);
					$ini->update([
						'product : common' => ['READ_HOST' => $host],
						'product : common' => ['WRITE_HOST' => $host],
						'product : common' => ['READ_PORT' => $port],
						'product : common' => ['WRITE_PORT' => $port],
						'product : common' => ['READ_USER' => $user],
						'product : common' => ['WRITE_USER' => $user],
						'product : common' => ['READ_PSWD' => $password],
						'product : common' => ['WRITE_PSWD' => $password],
						'product : common' => ['Default' => $dbname]
					]);
					$ini->write();
					
					$result = @file_put_contents(INSTALL_LOCK, 1);
					if (!$result){
						$data = array('code' => 1004, 'msg' =>"无法写入安装锁定到".INSTALL_LOCK."文件，请检查是否有写权限");
					}
					$data = array('code' => 1, 'msg' =>"SUCCESS");
				}
            } catch (PDOException $e) {
				$data = array('code' => 1001, 'msg' =>"失败:".$e->getMessage());
            }
		}else{
			$data = array('code' => 1000, 'msg' => '丢失参数');
		}
		Helper::response($data);
	}
}