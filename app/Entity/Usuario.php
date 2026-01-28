<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

class Usuario
{
  /**
   * Identificador unico do Usuário
   *
   * @var integer
   */
  public $id;

  /**
   * Nome do Usuário
   *
   * @var string
   */
  public $nome;

  /**
   * Email do Usuário
   *
   * @var string
   */
  public $email;

  /**
   * Hash da Senha do Usuário
   *
   * @var string
   */
  public $senha;

  /**
   * Método responsável por cadastrar um novo Usuário
   *
   * @return boolean
   */
  public function cadastrar()
  {
    //DATABASE
    $obDatabase = new Database('usuarios');

    //INSERE UM NOVO USUÁRIO
    $this->id = $obDatabase->insert([
      'nome' => $this->nome,
      'email' => $this->email,
      'senha' => $this->senha
    ]);

    //SUCESSO
    return true;
  }

  /**
   * Método reponsável por retornar uma instância de usuário pelo Email
   *
   * @param string $email
   * @return Usuario
   */
  public static function getUsuariosEmail($email)
  {

    return (new Database('usuarios'))->select('email = "' . $email . '"')->fetchObject(self::class);
  }
}
