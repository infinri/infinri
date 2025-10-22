<?php

use Infinri\Admin\Model\AdminUser;

describe('Admin User Model', function () {
    
    beforeEach(function () {
        $this->userData = [
            'user_id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'firstname' => 'Test',
            'lastname' => 'User',
            'password' => '$2y$13$hashedpassword',
            'roles' => '["ROLE_ADMIN","ROLE_USER"]',
            'is_active' => '1',
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00',
            'last_login_at' => '2025-01-01 00:00:00'
        ];
        
        // Mock the resource
        $mockResource = Mockery::mock(\Infinri\Admin\Model\ResourceModel\AdminUser::class);
        
        $this->user = new AdminUser($mockResource);
        $this->user->setData($this->userData);
    });
    
    test('can get user id', function () {
        expect($this->user->getUserId())->toBe(1);
    });
    
    test('can get username', function () {
        expect($this->user->getUsername())->toBe('testuser');
    });
    
    test('can get email', function () {
        expect($this->user->getEmail())->toBe('test@example.com');
    });
    
    test('can get firstname', function () {
        expect($this->user->getFirstname())->toBe('Test');
    });
    
    test('can get lastname', function () {
        expect($this->user->getLastname())->toBe('User');
    });
    
    test('can get full name', function () {
        expect($this->user->getFullName())->toBe('Test User');
    });
    
    test('can get password', function () {
        expect($this->user->getPassword())->toBe('$2y$13$hashedpassword');
    });
    
    test('can get roles as array', function () {
        $roles = $this->user->getRoles();
        
        expect($roles)->toBeArray()
            ->and($roles)->toContain('ROLE_ADMIN')
            ->and($roles)->toContain('ROLE_USER');
    });
    
    test('can check if user is active', function () {
        expect($this->user->isActive())->toBeTrue();
    });
    
    test('can check if user is admin', function () {
        expect($this->user->hasRole('ROLE_ADMIN'))->toBeTrue();
    });
    
    test('can check if user has specific role', function () {
        expect($this->user->hasRole('ROLE_USER'))->toBeTrue()
            ->and($this->user->hasRole('ROLE_NONEXISTENT'))->toBeFalse();
    });
    
    test('can set and get data', function () {
        $this->user->setData('custom_field', 'custom_value');
        
        expect($this->user->getData('custom_field'))->toBe('custom_value');
    });
    
    test('can convert to array', function () {
        $array = $this->user->toArray();
        
        expect($array)->toBeArray()
            ->and($array)->toHaveKey('user_id')
            ->and($array)->toHaveKey('username')
            ->and($array)->toHaveKey('email')
            ->and($array['username'])->toBe('testuser');
    });
    
    test('inactive user returns false for isActive', function () {
        $mockResource = Mockery::mock(\Infinri\Admin\Model\ResourceModel\AdminUser::class);
        $inactiveUser = new AdminUser($mockResource);
        
        $inactiveData = $this->userData;
        $inactiveData['is_active'] = '0';
        $inactiveUser->setData($inactiveData);
        
        expect($inactiveUser->isActive())->toBeFalse();
    });
    
    test('full name handles missing firstname', function () {
        $mockResource = Mockery::mock(\Infinri\Admin\Model\ResourceModel\AdminUser::class);
        $user = new AdminUser($mockResource);
        
        $data = $this->userData;
        $data['firstname'] = '';
        $user->setData($data);
        
        expect($user->getFullName())->toBe('User');
    });
    
    test('full name handles missing lastname', function () {
        $mockResource = Mockery::mock(\Infinri\Admin\Model\ResourceModel\AdminUser::class);
        $user = new AdminUser($mockResource);
        
        $data = $this->userData;
        $data['lastname'] = '';
        $user->setData($data);
        
        expect($user->getFullName())->toBe('Test');
    });
    
    test('roles handles invalid JSON gracefully', function () {
        $mockResource = Mockery::mock(\Infinri\Admin\Model\ResourceModel\AdminUser::class);
        $user = new AdminUser($mockResource);
        
        $data = $this->userData;
        $data['roles'] = 'invalid json';
        $user->setData($data);
        
        // Should return array (either empty or default roles, depending on implementation)
        expect($user->getRoles())->toBeArray();
    });
    
});

describe('Admin User Password Security', function () {
    
    test('password should be hashed with bcrypt', function () {
        $password = 'testpassword123';
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        expect($hashed)->toStartWith('$2y$')
            ->and(password_verify($password, $hashed))->toBeTrue();
    });
    
    test('different passwords produce different hashes', function () {
        $hash1 = password_hash('password1', PASSWORD_DEFAULT);
        $hash2 = password_hash('password1', PASSWORD_DEFAULT);
        
        // Even same password should have different hash due to salt
        expect($hash1)->not->toBe($hash2);
    });
    
    test('password verification works correctly', function () {
        $password = 'correctpassword';
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        expect(password_verify($password, $hashed))->toBeTrue()
            ->and(password_verify('wrongpassword', $hashed))->toBeFalse();
    });
    
});
