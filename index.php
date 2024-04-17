<?php

namespace App;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Tuupola\Middleware\CorsMiddleware;

require_once __DIR__ . '/vendor/autoload.php';



use Storage\{
  AccountStorage,
  BoardStorage,
  PDOManager as PDOManager,
  ProjectStorage,
  MailSenderStorage,
  UserStorage,
  TaskStorage,
  AttachmentStorage,
  CommentStorage,
  TaskFieldStorage,
  WorkspaceStorage,
  MembershipStorage
};

use Service\{
  AccountService,
  BoardService,
  ProjectService,
  MailSenderService,
  TaskService,
  UserService,
  AttachmentService,
  CommentService,
    MembershipService,
    TaskFieldService,
  WorkspaceService
};

use Controller\{
  AccountController,
  BoardController,
  ProjectController,
  MailSenderController,
  TaskController,
  UserController,
  AttachmentController,
  CommentController,
    MembershipController,
    TaskFieldController,
  WorkspaceController
};
use LDAP\Result;
use Middleware\EmailVerify;
use Middleware\TokenVerify;

$app = AppFactory::create();

//setup dependencies
$db = new PDOManager(null);

$projectStore = new ProjectStorage($db);
$projectService = new ProjectService($projectStore);
$projectController = new ProjectController($projectService);

$mailStore = new MailSenderStorage($db);
$mailService = new MailSenderService($mailStore);
$mailController = new MailSenderController($mailService);

$boardStore = new BoardStorage($db);
$boardService = new BoardService($boardStore);
$boardController = new BoardController($boardService);

$taskStore = new TaskStorage($db);
$taskService = new TaskService($taskStore);
$taskController = new TaskController($taskService);

$userStore = new UserStorage($db);
$userService = new UserService($userStore);
$userController = new UserController($userService);

$attachmentStore = new AttachmentStorage($db);
$attachmentService = new AttachmentService($attachmentStore);
$attachmentController = new AttachmentController($attachmentService);


$accountStore = new AccountStorage($db);
$accountService = new AccountService($accountStore);
$accountController = new AccountController($accountService);

$taskFieldStore = new TaskFieldStorage($db);
$taskFieldService = new TaskFieldservice($taskFieldStore);
$taskFieldController = new TaskFieldController($taskFieldService);

$workspaceStore = new WorkspaceStorage($db);
$workspaceService = new WorkspaceService($workspaceStore);
$workspaceController = new WorkspaceController($workspaceService);

$membershipStore = new MembershipStorage($db);
$membershipService = new MembershipService($membershipStore);
$membershipController = new MembershipController($membershipService);

//middleware

$emailVerify = new EmailVerify();


$app->add(new CorsMiddleware([
  "origin" => ["*"], // cho phép truy cập từ tất cả các trang
  "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"], // các phương thức HTTP được cho phép
  "headers.allow" => ["Authorization", "Content-Type", "X-Requested-With"],
  "headers.expose" => [],
  "credentials" => true,
  "cache" => 0,
]));

$app->group("/v1/workspaces", function ($workspace) {

  /*
   * Workspace 
   */
  $workspace->get("", function (Request $req, Response $res) {
  });
  $workspace->post("", function (Request $req, Response $res) {
  });

  /*
  * One workspace
  */
  $workspace->group("/{workspace_id}", function ($one_workspace) {

    $one_workspace->get("", function (Request $req, Response $res) {
    });
    $one_workspace->put("", function (Request $req, Response $res) {
    });
    $one_workspace->delete("", function (Request $req, Response $res) {
    });

    /*
    * Collaborators
    */
    $one_workspace->group("/collaborators", function ($members) {

      $members->get("", function (Request $req, Response $res) {
        global $workspaceController;
        return $workspaceController->GetUserOfWorkspace($req, $res);
      });
      $members->post("", function (Request $req, Response $res) {
      });

      /*
      * One Collobarators
      */
      $members->group("/{collaborators_id}", function ($one_member) {

        $one_member->get("", function (Request $req, Response $res) {
        });
        $one_member->put("", function (Request $req, Response $res) {
        });
        $one_member->delete("", function (Request $req, Response $res) {
        });
      });
    });

    /*
    * Projects
    */
    $one_workspace->group("/projects", function ($project) {

      $project->get("", function (Request $req, Response $res) {
      });
      $project->post("", function (Request $req, Response $res) {
      });

      /*
      * One project
      */
      $project->group("/{project_id}", function ($one_project) {

        $one_project->get("", function (Request $req, Response $res) {
          global $projectController;
          return $projectController->getAProject($req, $res);
        });

        $one_project->get("/activities", function(Request $req, Response $res){
          global $projectController;
          return $projectController->GetHistoryMember($req, $res);
        });

        $one_project->put("", function (Request $req, Response $res) {
          global $projectController;
          return $projectController->updateProject($req, $res);
        });

        $one_project->delete("", function (Request $req, Response $res) {
        });

        $one_project->group("/tasks", function ($task) {

          $task->get("", function (Request $req, Response $res) {
            global $taskController;
            return $taskController->GetTasksOfProject($req, $res);
          });
          $task->post("", function (Request $req, Response $res) {
          });

          $task->group("/{task_id}", function ($one_task) {

            $one_task->get("", function (Request $req, Response $res) {
            });
            $one_task->put("", function (Request $req, Response $res) {
              global $taskController;
              return $taskController->updateTask($req, $res);
            });
            $one_task->delete("", function (Request $req, Response $res) {
            });

            $one_task->group("/activities", function ($activity) {
              $activity->get("", function (Request $req, Response $res) {
              });
              $activity->post("", function (Request $req, Response $res) {
              });
            });

            $one_task->group("/attachments", function ($attachment) {

              $attachment->get("", function (Request $req, Response $res) {
              });
              $attachment->post("", function (Request $req, Response $res) {
              });
              $attachment->delete("/{attachment_id", function (Request $req, Response $res) {
              });
            });

            $one_task->group("/comments", function ($comment) {

              $comment->get("", function (Request $req, Response $res) {
              });
              $comment->post("", function (Request $req, Response $res) {
              });

              $comment->group("/{comment_id}", function ($one_comment) {

                $one_comment->put("", function (Request $req, Response $res) {
                });
                $one_comment->delete("", function (Request $req, Response $res) {
                });
              });
            });

            $one_task->get("time_field", function (Request $req, Response $res) {
            });
            $one_task->put("/time_field/{time_field_id}", function (Request $req, Response $res) {
            });
          });
        });

        $one_project->group("/boards", function ($board) {
          $board->get("", function (Request $req, Response $res) {
            global $boardController;
            return $boardController->GetBoads($req, $res);
          });
          $board->post("", function (Request $req, Response $res) {
            global $boardController;
            return $boardController->addBoards($req, $res);
          });

          $board->group("/{board_id}", function ($one_board) {
            $one_board->put("", function (Request $req, Response $res) {
              global $boardController;
              return $boardController->updateBoard($req, $res);
            });
            $one_board->put("/workflow", function (Request $req, Response $res){
              global $boardController;
              return $boardController->changeWorkflow($req, $res);
            });
            $one_board->delete("", function (Request $req, Response $res) {
              global $boardController;
              return $boardController->deleteBoard($req, $res);
            });
          });
        });

        $one_project->group("/members", function ($member) {

          $member->get("", function (Request $req, Response $res) {
            global $projectController;
            return $projectController->GetUserOfProject($req, $res);
          });
          $member->post("", function (Request $req, Response $res) {
            global $membershipController;
            return $membershipController->CreateMembership($req, $res);
          });

          $member->group("/{user_id}", function ($one_member) {

            $one_member->get("", function (Request $req, Response $res) {
            });
            $one_member->put("", function (Request $req, Response $res) {
            });
            $one_member->delete("", function (Request $req, Response $res) {
              global $membershipController;
              return $membershipController->RemoveMembership($req, $res);
            });
          });
        });

        $one_project->group("task_fields", function ($task_field) {

          $task_field->get("", function (Request $req, Response $res) {
          });
          $task_field->post("", function (Request $req, Response $res) {
          });

          $task_field->put("/{task_field_id", function (Request $req, Response $res) {
          });
          $task_field->delete("/{task_field_id", function (Request $req, Response $res) {
          });
        });
      });
    });
  });
});



$app->group("/v1/users", function ($user) use ($workspaceController, $projectController, $userController) {
  $user->get("/{user_id}/workspaces", function (Request $req, Response $res) use ($workspaceController) {
    return $workspaceController->GetWorkspacesOfUser($req, $res);
  });
  $user->get("/{user_id}/workspaces/{workspace_id}/projects", function (Request $req, Response $res) use ($projectController) {
    return $projectController->GetProjectOfUser($req, $res);
  });
  $user->get("/{user_id}", function (Request $req, Response $res) use ($userController) {
    return $userController->GetAnUser($req, $res);
  });
  $user->get("", function (Request $req, Response $res) {
  });
  $user->put("/{user_id}", function (Request $req, Response $res) {
  });
  $user->put("/{user_id}/password", function (Request $req, Response $res) {
  });
});


// $app->group("/v1/workspaces", function ($workspace) use ($workspaceController, $projectController, $boardController, $taskController) {

//   $workspace->post("", function (Request $req, Response $res) use ($workspaceController) {
//     return $workspaceController->CreateWorkspace($req, $res);
//   });


//   $workspace->group("/{workspace_id}/projects", function ($project) use ($projectController, $boardController, $taskController) {

//     /*
//     * Create a project  
//     */
//     $project->post("", function (Request $req, Response $res) use ($projectController) {
//       return $projectController->CreateProject($req, $res);
//     });

//     /*
//     * Edit a project
//     */


//     /*
//     * Delete a project
//     */
//     $project->delete("/{project_id}", function (Request $req, Response $res) use ($projectController) {
//       return $projectController->deleteProject($req, $res);
//     });

//     /*
//     * Get a project 
//     */
//     $project->get("/{project_id}", function (Request $req, Response $res) use ($projectController) {
//       return $projectController->getAProject($req, $res);
//     });
//     $project->get("/{project_id}/tasks", function (Request $req, Response $res) use ($taskController) {
//       return $taskController->GetTasksOfProject($req, $res);
//     });
//     /*
//     * Board
//     */
//     $project->group("/{project_id}/boards", function ($board) use ($boardController) {

//       /*
//       * Create a board
//       */
//       $board->post("", function (Request $req, Response $res) use ($boardController) {
//         return $boardController->addBoards($req, $res);
//       });

//       /*
//       * Update a board
//       */
//       $board->put("/{board_id}", function (Request $req, Response $res) use ($boardController) {
//         return $boardController->changeWorkflow($req, $res);
//       });
//     });
//   });
// });



// // $app->group("/v1/projects", function ($project) use ($projectController, $boardController) {

// //   /*
// //   * Create a project  
// //   */
// //   $project->post("", function (Request $req, Response $res) use ($projectController) {
// //     return $projectController->CreateProject($req, $res);
// //   });

// //   /*
// //   * Edit a project
// //   */
// //   $project->put("/{project_id}", function (Request $req, Response $res) use ($projectController) {
// //     return $projectController->updateProject($req, $res);
// //   });

// //   /*
// //   * Delete a project
// //   */
// //   $project->delete("/{project_id}", function (Request $req, Response $res) use ($projectController) {
// //     return $projectController->deleteProject($req, $res);
// //   });

// //   /*
// //   * Get a project 
// //   */
// //   $project->get("/{project_id}", function (Request $req, Response $res) use ($projectController) {
// //     return $projectController->getAProject($req, $res);
// //   });

// //   /*
// //   * Board
// //   */
// //   $project->group("/{project_id}/boards", function ($board) use ($boardController) {

// //     /*
// //     * Create a board
// //     */
// //     $board->post("", function (Request $req, Response $res) use ($boardController) {
// //       return $boardController->addBoards($req, $res);
// //     });

// //     /*
// //     * Update a board
// //     */
// //     $board->put("/{board_id}", function (Request $req, Response $res) use ($boardController) {
// //       return $boardController->changeWorkflow($req, $res);
// //     });
// //   });
// // });

// /*
//  * *Project
//  */

// // $app->post("/v1/projects", function (Request $req, Response $res) use ($projectController) {
// //   return $projectController->CreateProject($req, $res);
// // });

// $app->get("/v1/projects", function (Request $req, Response $res) use ($projectController) {
//   return $projectController->getAllProject($req, $res);
// });
// // $app->get("/v1/projects/{project_id}", function (Request $req, Response $res) use ($projectController) {
// //   return $projectController->getAProject($req, $res);
// // });

// // $app->put("/v1/projects/{project_id}", function (Request $req, Response $res) use ($projectController) {
// //   return $projectController->updateProject($req, $res);
// // });

// // $app->delete("/v1/projects/{project_id}", function (Request $req, Response $res) use ($projectController) {
// //   return $projectController->deleteProject($req, $res);
// // });

// $app->get("/v1/projects/{project_id}/task_fields", function (Request $req, Response $res) use ($taskFieldController) {
//   return $taskFieldController->GetTaskFieldByProjectID($req, $res);
// });
// /*
// * * Board
// */

// $app->get("/v1/projects/{project_id}/boards", function (Request $req, Response $res) use ($boardController) {
//   return $boardController->GetBoads($req, $res);
// });

// $app->delete("/v1/projects/{project_id}/boards/{board_id}", function (Request $req, Response $res) use ($boardController) {
//   return $boardController->deleteBoard($req, $res);
// });
// // $app->post("/v1/projects/{project_id}/boards", function (Request $req, Response $res) use ($boardController) {
// //   return $boardController->addBoards($req, $res);
// // });

// // $app->put("/v1/projects/{project_id}/boards/{board_id}", function (Request $req, Response $res) use ($boardController) {
// //   return $boardController->changeWorkflow($req, $res);
// // });

// /*
// * * Task
// */
// $app->post("/v1/projects/{project_id}/tasks", function (Request $req, Response $res) use ($taskController) {
//   return $taskController->AddATask($req, $res);
// });


// $app->put("/v1/projects/{project_id}/tasks/{task_id}/status", function (Request $req, Response $res) use ($taskController) {
//   return $taskController->updateStatus($req, $res);
// });

// $app->put("/v1/projects/{project_id}/tasks/{task_id}/assignments", function (Request $req, Response $res) use ($taskController) {
//   return $taskController->updateAssignedUSer($req, $res);
// });

// $app->put("/v1/projects/{project_id}/tasks/{task_id}", function (Request $req, Response $res) use ($taskController) {
//   return $taskController->updateTask($req, $res);
// });


// /*
// * * attachments
// */
// $app->post("/v1/projects/{project_id}/tasks/{task_id}/attachments", function (Request $req, Response $res) use ($attachmentController) {
//   return $attachmentController->InsertAttachment($req, $res);
// });

// $app->put("/v1/projects/{project_id}/tasks/{task_id}/attachments/{attchment_id}", function (Request $req, Response $res) use ($attachmentController) {
//   return $attachmentController->updateAttachment($req, $res);
// });
// $app->delete("/v1/projects/{project_id}/tasks/{task_id}/attachments/{attchment_id}", function (Request $req, Response $res) use ($attachmentController) {
//   return $attachmentController->deleteAttachment($req, $res);
// });
// /*
// * *comment
// */
// $app->put("/v1/projects/{project_id}/tasks/{task_id}/comments/{comment_id}", function (Request $req, Response $res) use ($commentController) {
//   return $commentController->updateComment($req, $res);
// });

// $app->delete("/v1/projects/{project_id}/tasks/{task_id}/comments/{comment_id}", function (Request $req, Response $res) use ($commentController) {
//   return $commentController->deleteComment($req, $res);
// });

// $app->get("/v1/projects/{project_id}/tasks/{task_id}/comments", function (Request $req, Response $res) use ($commentController) {
//   return $commentController->getCommentOfComment($req, $res);
// });

/* 
* * OTP
*/

$app->post("/v1/send_mail/otp", function (Request $req, Response $res) use ($mailController) {
  return $mailController->sendOTP($req, $res);
});

$app->post("/v1/verify/otp", function (Request $req, Response $res) use ($mailController) {
  return $mailController->verifyOtp($req, $res);
});

/*
* * Auth
*/

$app->post(
  "/v1/login",
  function (Request $req, Response $res) use ($accountController) {
    return $accountController->Login($req, $res);
  }
);

$app->post("/v1/register", function (Request $req, Response $res) use ($accountController) {
  return $accountController->Register($req, $res);
})->add(new EmailVerify())->add(new TokenVerify());



$app->run();
