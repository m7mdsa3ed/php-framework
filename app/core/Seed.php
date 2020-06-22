<?php

namespace App\Core;

use Faker\Factory as Faker;

class Seed {

  private $DBNAME;
  private $connection;

  function __construct() {
    $this->connection = DB::getInstance()->getConnection();
  }

  function init(){    

    $dbname = $this->DBNAME =  env('DBNAME');

    $this->dropTable('product_category');
    $this->execute_sql(" CREATE TABLE `$dbname`.`product_category` ( 
      `id` INT(11) NOT NULL AUTO_INCREMENT , 
      `product_id` INT(11) NOT NULL , 
      `category_id` INT(11) NOT NULL , 
      PRIMARY KEY (`id`)
    );");
    
    $this->dropTable('comments');
    $this->execute_sql(" CREATE TABLE `$dbname`.`comments` ( 
      `id` INT(11) NOT NULL AUTO_INCREMENT , 
      `user_id` INT(11) NOT NULL , 
      `product_id` INT(11) NOT NULL , 
      `body` TEXT NOT NULL , 
      `created_at` TIMESTAMP NULL DEFAULT NULL , 
      `updated_at` TIMESTAMP NULL DEFAULT NULL , 
      PRIMARY KEY (`id`)
    );");

    $this->dropTable('products');
    $this->execute_sql(" CREATE TABLE `$dbname`.`products` ( 
      `id` INT(11) NOT NULL AUTO_INCREMENT , 
      `user_id` INT(11) NOT NULL , 
      `name` VARCHAR(255) NOT NULL , 
      `price` INT(11) NOT NULL, 
      `description` TEXT NOT NULL , 
      `excerpt` TEXT NULL DEFAULT NULL,
      `image` TEXT NULL DEFAULT NULL, 
      `quantity` INT(11) NULL DEFAULT 0, 
      `created_at` TIMESTAMP NULL DEFAULT NULL , 
      `updated_at` TIMESTAMP NULL DEFAULT NULL , 
      PRIMARY KEY (`id`)
    );");
    
    $this->dropTable('categories');
    $this->execute_sql(" CREATE TABLE `$dbname`.`categories` ( 
      `id` INT(11) NOT NULL AUTO_INCREMENT , 
      `name` VARCHAR(255) NOT NULL , 
      `description` TEXT NULL , 
      `created_at` TIMESTAMP NULL DEFAULT NULL , 
      `updated_at` TIMESTAMP NULL DEFAULT NULL , 
      PRIMARY KEY (`id`)
    );");

    $this->dropTable('users');
    $this->execute_sql(" CREATE TABLE `$dbname`.`users` ( 
      `id` INT(11) NOT NULL AUTO_INCREMENT , 
      `username` VARCHAR(255) NOT NULL , 
      `email` VARCHAR(255) NOT NULL , 
      `password` VARCHAR(255) NOT NULL , 
      `name` VARCHAR(255) NULL DEFAULT NULL , 
      `position` VARCHAR(255) NULL DEFAULT NULL , 
      `bio` TEXT NULL DEFAULT NULL , 
      `avatar` TEXT NULL DEFAULT NULL, 
      `created_at` TIMESTAMP NULL DEFAULT NULL , 
      `updated_at` TIMESTAMP NULL DEFAULT NULL , 
      PRIMARY KEY (`id`), 
      UNIQUE (`username`), 
      UNIQUE (`email`) 
    );");

    $this->dropTable('viewers');
    $this->execute_sql(" CREATE TABLE `$dbname`.`viewers` ( 
      `id` INT(11) NOT NULL AUTO_INCREMENT , 
      `user_ip` VARCHAR(15) NOT NULL , 
      `product_id` INT(11) NOT NULL , 
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
      PRIMARY KEY (`id`)
    );");
    
  }

  function feed() {

    $faker = Faker::create();
    
    $products = 100;
    $users = 10;
    $comments = 500;
    $viewers = 100;
    $categories = 10;

    $this->create('users',[
      'username'  => 'admin',
      'email'     => 'admin@mohamedsaeed.ml.com',
      'password'  => password_hash('admin', PASSWORD_DEFAULT),
      'name'      => 'Admin',
      'position'  => 'CEO',
      'bio'       => $faker->text(rand(500, 700)),
      'created_At'=> Date('Y-m-d H:i:s')
    ]);

    for($i=0; $i<$users; $i++) {
      $this->create('users',[
        'username'  => $faker->userName,
        'password'  => password_hash('123456', PASSWORD_DEFAULT),
        'name'      => $faker->name,
        'email'     => $faker->email,
        'position'  => $faker->randomElement(['Partner', 'Funder']),
        'bio'       => $faker->text(rand(500, 700)),
        'created_at'=> $faker->dateTimeThisDecade()->format('Y-m-d H:i:s')
      ]);
    }

    for($i=0; $i<$products; $i++) {
      $this->create('products',[
        'user_id'       => $faker->numberBetween(1,$users),
        'name'          => $faker->sentences(1, true),
        'price'         => $faker->numberBetween(100, 500),
        'description'   => $faker->sentences($faker->numberBetween(50,70), true),
        'excerpt'       => $faker->sentences($faker->numberBetween(10,15), true),
        'quantity'      => $faker->numberBetween(0, 20),
        'created_at'    => $faker->dateTimeThisDecade()->format('Y-m-d H:i:s')
      ]);
    }

    for($i=0; $i<$categories; $i++) {
      $this->create('categories',[
        'name'        => $faker->word(),
        'description' => $faker->sentences($faker->numberBetween(5,15), true),
      ]);
    }

    for($i=0; $i<$categories; $i++) {
      $this->create('product_category',[
        'product_id'      => $faker->numberBetween(1,$products),
        'category_id'  => $faker->numberBetween(1,$categories),
      ]);
    }

    for($i=0; $i<$comments; $i++) {
      $this->create('comments',[
        'user_id'      => $faker->numberBetween(1,$users),
        'product_id'      => $faker->numberBetween(1,$products),
        'body'         => $faker->sentences($faker->numberBetween(5,15), true),
      ]);
    }

    for($i=0; $i<$viewers; $i++) {
      $this->create('viewers',[
        'user_ip'      => $faker->ipv4(),
        'product_id'      => $faker->numberBetween(1,$users),
      ]);
    }
  }

  function create($table, $fields) {
		if (count($fields)) {
			$keys = array_keys($fields);
			$values = '';
			$x = 1;
			foreach ($fields as $field) {
				$values .= "?";
				if ($x < count($fields)) {
					$values .= ", ";
				}
				$x++;
			}
      $sql = " INSERT INTO {$table} ( `" . implode('`, `', $keys) . "`) VALUES ({$values}) ";
      if ( $stmt = $this->connection->prepare($sql) ) {
        if (count($fields)) {
          $i=1;
          foreach ($fields as $value) {
            $stmt->bindValue($i, $value);
            $i++;
          }
        }
       $stmt->execute();
      } 			
		}
	}

  function dropTable($tname) {
    $SQL = "DROP TABLE IF EXISTS `$this->DBNAME`.$tname";
    $drop = $this->connection->prepare($SQL);
    $drop->execute();
  }

  function execute_sql($SQL) {
    $stmt = $this->connection->prepare($SQL);
    $stmt->execute();    
  }

}
