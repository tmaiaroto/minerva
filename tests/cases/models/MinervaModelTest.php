<?php
/**
 * Minerva: a CMS based on the Lithium PHP framework
 *
 * @copyright Copyright 2010-2011, Shift8Creative (http://www.shift8creative.com)
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
*/
namespace minerva\tests\cases\models;

use minerva\models\User;
use minerva\models\MinervaModel;

class MinervaModelTest extends \lithium\test\Unit {
    
    public function setUp() {}

    public function tearDown() {
    }
    
    public function testDisplayName() {
        $result = User::displayName();
        $this->assertEqual('User', $result);
        
        $name = 'Cool Pages!';
        MinervaModel::displayName($name);
        $result = MinervaModel::displayName();
        $this->assertEqual($name, $result);
    }
    
    public function testLibraryName() {
        $result = MinervaModel::libraryName();
        $this->assertEqual('minerva', $result);
    }
    
    public function testValidationRules() {
        $result = MinervaModel::validationRules();
        $this->assertTrue(is_array($result));
        
        $custom_rules = array('title' => array(array('notEmpty', 'message' => 'Title cannot be empty')));
        MinervaModel::validationRules($custom_rules);
        $result = MinervaModel::validationRules();
        $this->assertEqual($custom_rules, $result);
    }
    
    public function testAccessRules() {
        $result = MinervaModel::accessRules();
        $this->assertTrue(is_array($result));
        
        $custom_rules = array('index' => array('action' => array(array('rule' => 'denyAll'))));
        MinervaModel::accessRules($custom_rules);
        $result = MinervaModel::accessRules();
        $this->assertEqual($custom_rules, $result);
    }
    
    public function testActionRedirects() {
        $result = MinervaModel::actionRedirects();
        $this->assertTrue(is_array($result));
        
        $custom_redirects = array('index' => '/');
        MinervaModel::actionRedirects($custom_redirects);
        $result = MinervaModel::actionRedirects();
        $this->assertEqual($custom_redirects, $result);
    }
    
    public function testUrlField() {
        $result = User::urlField();
        $this->assertEqual(array('first_name', 'last_name'), $result);
        
        $field = 'title';
        MinervaModel::urlField($field);
        $result = MinervaModel::urlField();
        $this->assertEqual($field, $result);
        
        $field = array('first_name', 'last_name');
        MinervaModel::urlField($field);
        $result = MinervaModel::urlField();
        $this->assertEqual($field, $result);
    }
    
    public function testUrlSeparator() {
        $result = MinervaModel::urlSeparator();
        $this->assertEqual('-', $result);
        
        $separator = '_';
        MinervaModel::urlSeparator($separator);
        $result = MinervaModel::urlSeparator();
        $this->assertEqual($separator, $result);
    }
    
    public function testSearchSchema() {
        $result = MinervaModel::searchSchema();
        $this->assertTrue(is_array($result));
        
        $schema = array('title' => array('weight' => 1));
        MinervaModel::searchSchema($schema);
        $result = MinervaModel::searchSchema();
        $this->assertEqual($schema, $result);
    }
    
    public function testGetMineraModel() {
        $result = MinervaModel::getMinervaModel('page');
        $this->assertEqual('minerva\models\Page', $result);
        
        $result = MinervaModel::getMinervaModel('page', 'minerva');
        $this->assertEqual('minerva\models\Page', $result);
    }
    
    public function testGetAllMinervaModels() {
        $result = MinervaModel::getAllMinervaModels();
        $this->assertTrue(is_null($result) || is_array($result));
    }
    
}
?>