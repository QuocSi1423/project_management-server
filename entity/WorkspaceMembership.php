<?php

namespace Entity;

use JsonSerializable;

class WorkspaceMembership implements JsonSerializable {
    private ?Member $user;
    private ?Workspace $workspace;
    private ?int $role;

    public function __construct(?Member $user, ?Workspace $workspace, ?int $role) {
        $this->user = $user;
        $this->workspace = $workspace;
        $this->role = $role;
    }

    public function getUser(): ?Member {
        return $this->user;
    }

    public function setUser(?member $user): void {
        $this->user = $user;
    }

    public function getWorkspace(): ?Workspace {
        return $this->workspace;
    }

    public function setWorkspace(?Workspace $workspace): void {
        $this->workspace = $workspace;
    }

    public function getRole(): ?int {
        return $this->role;
    }

    public function setRole(?int $role): void {
        $this->role = $role;
    }

    public function jsonSerialize() {
        return [
            'user' => $this->user,
            'workspace' => $this->workspace,
            'role' => $this->role
        ];
    }
}
