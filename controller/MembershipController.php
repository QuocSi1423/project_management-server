<?php

namespace Controller;

use Entity\Membership;
use Exception;
use Firebase\JWT\ExpiredException;
use Service\MembershipService;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class MembershipController
{
  private MembershipService $service;

  public function __construct(MembershipService $service)
  {
    $this->service = $service;
  }

  public function CreateMembership(Request $req, Response $res)
  {
    try {
      $body = $req->getBody()->getContents();
      $data = json_decode($body);
  
      if(!isset($data->user_id)){
        throw new Exception("User id is required");
      }

      if(!isset($data->role)){
        throw new Exception("Role is required");
      }

      $project_id = $req->getAttribute('project_id');
      $user_id = $data->user_id;
      $role = $data->role;

      $membership = new Membership($user_id, $project_id, $role);
      $this->service->addMembership($membership);
      $res = $res->withStatus(200);
      $res->getBody("successfully created");
      return $res;


    } catch (Exception $e) {
      if($e->getCode() == 400){
        $res = $res->withStatus(400);
      }else if($e->getCode() == 409){
        $res = $res->withStatus(409);
      }else{
        $res = $res->withStatus(500);
      }
      $res->getBody()->write($e->getMessage());
      return $res;
    }
  }

  public function RemoveMembership(Request $req, Response $res){
    try{
      $user_id = $req->getAttribute('user_id');
      $project_id = $req->getAttribute('project_id');

      $this->service->deleteMembership($user_id, $project_id);
      $res = $res->withStatus(200);
      $res->getBody()->write(json_encode("successfully deleted"));
      return $res;
    }catch(Exception $e){
      $res = $res->withStatus(500);
      $res->getBody()->write(json_encode($e->getMessage()));
      return $res;
    }
  }
}
