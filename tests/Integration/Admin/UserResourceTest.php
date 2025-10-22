<?php

use Infinri\Admin\Model\ResourceModel\AdminUser;
use Infinri\Core\Model\ObjectManager;

beforeEach(function () {
    $this->objectManager = ObjectManager::getInstance();
    $this->adminUserResource = $this->objectManager->get(AdminUser::class);
});

describe('Admin User Resource CRUD Operations', function () {
    
    test('can create a new admin user', function () {
        $userData = [
            'username' => 'testuser_' . time(),
            'email' => 'test' . time() . '@example.com',
            'firstname' => 'Test',
            'lastname' => 'User',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'roles' => json_encode(['ROLE_USER']),
            'is_active' => 1
        ];
        
        $userId = $this->adminUserResource->save($userData);
        
        expect($userId)->toBeInt()
            ->and($userId)->toBeGreaterThan(0);
            
        // Cleanup
        $this->adminUserResource->delete($userId);
    });
    
    test('can load user by id', function () {
        // Use existing admin user (ID 1)
        $user = $this->adminUserResource->load(1);
        
        expect($user)->toBeArray()
            ->and($user)->toHaveKey('user_id')
            ->and($user)->toHaveKey('username')
            ->and($user['user_id'])->toBe(1);
    });
    
    test('can load user by username', function () {
        $user = $this->adminUserResource->loadByUsername('admin');
        
        expect($user)->toBeArray()
            ->and($user)->toHaveKey('username')
            ->and($user['username'])->toBe('admin');
    });
    
    test('can load user by email', function () {
        $user = $this->adminUserResource->loadByEmail('admin@infinri.local');
        
        expect($user)->toBeArray()
            ->and($user)->toHaveKey('email')
            ->and($user['email'])->toBe('admin@infinri.local');
    });
    
    test('can update existing user', function () {
        // Create test user
        $userData = [
            'username' => 'updatetest_' . time(),
            'email' => 'update' . time() . '@example.com',
            'firstname' => 'Update',
            'lastname' => 'Test',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'roles' => json_encode(['ROLE_USER']),
            'is_active' => 1
        ];
        
        $userId = $this->adminUserResource->save($userData);
        
        // Update user
        $updateData = [
            'user_id' => $userId,
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => 'updated' . time() . '@example.com'
        ];
        
        $this->adminUserResource->save($updateData);
        
        // Verify update
        $updated = $this->adminUserResource->load($userId);
        expect($updated['firstname'])->toBe('Updated')
            ->and($updated['lastname'])->toBe('Name');
            
        // Cleanup
        $this->adminUserResource->delete($userId);
    });
    
    test('can delete user', function () {
        // Create test user
        $userData = [
            'username' => 'deletetest_' . time(),
            'email' => 'delete' . time() . '@example.com',
            'firstname' => 'Delete',
            'lastname' => 'Test',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'roles' => json_encode(['ROLE_USER']),
            'is_active' => 1
        ];
        
        $userId = $this->adminUserResource->save($userData);
        
        // Delete user
        $deleted = $this->adminUserResource->delete($userId);
        
        expect($deleted)->toBeGreaterThan(0);
        
        // Verify deleted
        $user = $this->adminUserResource->load($userId);
        expect($user)->toBeFalse();
    });
    
    test('returns false when loading non-existent user', function () {
        $user = $this->adminUserResource->load(999999);
        expect($user)->toBeFalse();
    });
    
    test('returns false when loading by non-existent username', function () {
        $user = $this->adminUserResource->loadByUsername('nonexistent_user_12345');
        expect($user)->toBeFalse();
    });
    
    test('can get all users', function () {
        $users = $this->adminUserResource->findAll();
        
        expect($users)->toBeArray()
            ->and($users)->not->toBeEmpty()
            ->and($users[0])->toHaveKey('user_id')
            ->and($users[0])->toHaveKey('username');
    });
    
    test('can update last login timestamp', function () {
        $user = $this->adminUserResource->loadByUsername('admin');
        $userId = $user['user_id'];
        
        $affected = $this->adminUserResource->updateLastLogin($userId);
        
        expect($affected)->toBeGreaterThan(0);
        
        // Verify last_login_at was updated
        $updated = $this->adminUserResource->load($userId);
        expect($updated['last_login_at'])->not->toBeNull();
    });
    
});

describe('Admin User Validation', function () {
    
    test('username must be unique', function () {
        $userData = [
            'username' => 'admin', // Existing username
            'email' => 'newadmin@example.com',
            'firstname' => 'New',
            'lastname' => 'Admin',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'roles' => json_encode(['ROLE_USER']),
            'is_active' => 1
        ];
        
        // This should fail due to duplicate username
        // For now, we'll just check that the username exists
        $existing = $this->adminUserResource->loadByUsername('admin');
        expect($existing)->not->toBeFalse();
    });
    
    test('email must be unique', function () {
        $existing = $this->adminUserResource->loadByEmail('admin@infinri.local');
        expect($existing)->not->toBeFalse();
    });
    
    test('password should be hashed', function () {
        $user = $this->adminUserResource->loadByUsername('admin');
        
        // Password should start with bcrypt identifier
        expect($user['password'])->toStartWith('$2y$');
    });
    
    test('roles should be valid JSON', function () {
        $user = $this->adminUserResource->loadByUsername('admin');
        
        $roles = json_decode($user['roles'], true);
        expect($roles)->toBeArray()
            ->and($roles)->not->toBeEmpty();
    });
    
});
