<?php
declare(strict_types=1);

namespace Tests\Support;

use DBConnectionClass;
use DAO;
use mysqli;
use Params;
use PHPUnit\Framework\TestCase;
use Session;
use View;

abstract class DatabaseTestCase extends TestCase
{
    protected mysqli $db;
    protected DAO $dao;

    protected function setUp(): void
    {
        parent::setUp();
        FactoryClock::reset();
        TestMailTrap::reset();
        $this->resetOsclassGlobals();

        $this->db = DBConnectionClass::newInstance()->getOsclassDb();
        $this->dao = new DAO();
        $this->db->begin_transaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) {
            $this->db->rollback();
        }
        $this->resetOsclassGlobals();
        TestMailTrap::reset();
        FactoryClock::reset();
        parent::tearDown();
    }

    protected function resetOsclassGlobals(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_FILES = [];
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $_SERVER["HTTP_USER_AGENT"] = "PHPUnit-FactoryTest/1.0";

        if (class_exists(Params::class)) {
            Params::init();
        }
        if (class_exists(Session::class)) {
            $s = Session::newInstance();
            $s->_drop("userId");
            $s->_drop("userName");
            $s->_drop("userEmail");
            $s->_drop("adminId");
            $s->_drop("adminUserName");
        }
        if (class_exists(View::class)) {
            View::newInstance()->_erase("logged_user"); View::newInstance()->_erase("user"); View::newInstance()->_erase("item");
        }
    }

    protected function loginAsUser(int $userId, string $name = "Test User", string $email = "user@example.test"): void
    {
        $s = Session::newInstance();
        $s->_set("userId", $userId);
        $s->_set("userName", $name);
        $s->_set("userEmail", $email);
        View::newInstance()->_exportVariableToView("logged_user", [
            "pk_i_id" => $userId,
            "s_name" => $name,
            "s_email" => $email,
        ]);
    }

    protected function loginAsAdmin(int $adminId = 1, string $username = "admin"): void
    {
        $s = Session::newInstance();
        $s->_set("adminId", $adminId);
        $s->_set("adminUserName", $username);
    }

    protected function setRequestParams(array $params, string $method = "GET"): void
    {
        $_SERVER["REQUEST_METHOD"] = strtoupper($method);
        foreach ($params as $k => $v) {
            if ($method === "POST") {
                $_POST[$k] = $v;
            } else {
                $_GET[$k] = $v;
            }
            $_REQUEST[$k] = $v;
            Params::setParam($k, $v);
        }
    }

    protected function captureOutput(callable $fn): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            $fn();
            return (string) ob_get_clean();
        } catch (TestTerminationException $e) {
            $buffered = (string) ob_get_clean();
            return $buffered !== "" ? $buffered : $e->output;
        } catch (\Throwable $t) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $t;
        }
    }
}
