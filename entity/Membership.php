<?php
namespace Entity;

use JsonSerializable;

class Membership implements JsonSerializable {
    private ?String $user_id;
    private ?String $project_id;
    private ?String $role;

    public function __construct(?String $user_id = null, ?String $project_id = null, ?String $role = null) {
        $this->user_id = $user_id;
        $this->project_id = $project_id;
        $this->role = $role;
    }

    public function getUserId(): ?String {
        return $this->user_id;
    }

    public function setUserId(?String $user_id): void {
        $this->user_id = $user_id;
    }

    public function getProjectId(): ?String {
        return $this->project_id;
    }

    public function setProjectId(?String $project_id): void {
        $this->project_id = $project_id;
    }

    public function getRole(): ?String {
        return $this->role;
    }

    public function setRole(?String $role): void {
        $this->role = $role;
    }

    public function jsonSerialize() {
        return [
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'role' => $this->role
        ];
    }
}
