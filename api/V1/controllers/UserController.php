<?php
namespace API\V1\Controllers;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response\JsonResponse;
use Valitron\Validator;
use Exception;
use API\V1\Models\User;

class UserController
{
    /**
     * Get Single User.
     * 
     * @param int $id
     * @return object 
     * 
     */

    public function show($id)
    {
        $user = new User();
        return $user->getUser($id);
    }

     /**
     * Create User.
     * 
     * @param array $request
     * @return object 
     * 
     */
    public function create(ServerRequest $request)
    {
        $data = $request->getParsedBody(); //get array data from request

        //Validate
        $validate = new Validator($data);
        $validate->rule('required', ['name', 'email', 'password']);
        $validate->rule('email', 'email');

        if ($validate->validate())
        {   
            $user = new User();
            return $user->createUser($data);

        }
        else
        {   
          return new JsonResponse(["errors" => $validate->errors()]);  
        }
    }
}