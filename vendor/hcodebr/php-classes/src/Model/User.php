<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class User extends Model {

    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";

    public static function login($login, $password)
    {
     
        $sql = new Sql();

          $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
                ":LOGIN"=>$login
          ));

          if (count($results) === 0)
          {
              throw new \Exception("User inexistente ou senha invalida", 1);
          }

          $data = $result[0];

        if (password_verify($password, $data["despassword"]) === true)
        {

            $user = new User();

            $user->setData($data);

           $_SESSION[User::SESSION] = $user->getValues();
           
            return $user;


        } else{

            throw new \Exception("User inexistente ou senha invalida", 1);

        }

    }

    public static function verifyLogin($inadmin = true)
    {

        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
            ||
            (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
        ) {

            header("Location: /admin/login");
            exit;

        };
       
    }
     
    public static function logout()
    {

        $_SESSION[User::SESSION] = NULL;

    }

    public static function listAll()
    {

        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b using(idperson) ORDER BY b.desperson");

    }

    public function save()
    {

        $sql = new Sql();
       
        $results = $sql->select("CALL sp_users_save(:deperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",array(
            ":deperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);

    }

    public function get($iduser)
    {

        $sql = new Sql();

        $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser", array(
            ":iduser"=>$iduser
        ));

        $this->setData($results[0]);

    }

    public function update()
    {

        $sql = new Sql();
       
        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :deperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",array(
            ":iduser"=>$this->getiduser(),
            ":deperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);

    }

    public function delete()
    {

        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser", array(
            ":iduser"=>$this->getiduser()
        ));

    }

    public static function getForgot($email)
    {
    
        $sql = new Sql();
    
        $results = $sql->select("
            SELECT * 
            FROM tb_persons a 
            INNER JOIN tb_users b USING(idperson)
            WHERE a.desemail = :email;
            ", array (
                ":email"=>$email
            ));
    
            if (count($results) === 0)
            {
    
                throw new \Exception("Falha ao recuperar senha.");
    
            }
            else
            {
                
                $data = $results[0];
    
               
    
                if (count($results2) === 0)
                {
    
                    throw new \Exception("Falha ao recuperar senha.");
    
                }
                else
                {
    
                    $dataRecovery = $results2[0];
    
                    $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"],
                     MCRYPT_MODE_ECB));
    
                    $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code"; 
    
                    $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha!", "forgot",
                        array(
                            "name"=>$data["desperson"],
                            "link"=>$link
                        ));
    
                        $mialer->send();
    
                        return $data;
    
                }
    
            }
    
    }

    public static function validForgotDecrypt($code)
    {

        $idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);

        $sql = new Sql();

        $results = $sql->select("
        SELECT *
        FROM tb_userspasswordsrecoveries a
        INNER JOIN tb_users b USING(iduser)
        INNER JOIN tb_persons c USING(idperson)
        WHERE
            a.idrecovery = :idrecovery
            AND
            a.dtrecovery IS NULL
            AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
        ", array(
            ":idrecovery"=>$idrecovery
        ));

        if (count($results) === 0)
        {
            throw new \Exception("Falha ao recuperar senha!");
        }
        else
        {
            return $results[0];
        }


    }

    public static function setForgotUsed($idrecover)
    {

        $sql = new Sql();

        $sql->querry("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
            ":idrecovery"=>$idrecovery
        ));

    }

    public function setPassword($password)
    {

        $sql = new Sql();

        $sql->querry("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password"=>$password,
            ":iduser"=>$this->getiduser()
        ));

    }




 
}






?>