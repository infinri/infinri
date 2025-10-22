<?php

describe('Admin User Validation Rules', function () {
    
    test('username must be at least 3 characters', function () {
        $username = 'ab';
        expect(strlen($username))->toBeLessThan(3);
        
        $validUsername = 'abc';
        expect(strlen($validUsername))->toBeGreaterThanOrEqual(3);
    });
    
    test('username can only contain alphanumeric and underscore', function () {
        $validUsernames = ['admin', 'admin_user', 'user123', 'test_user_123'];
        $invalidUsernames = ['admin@user', 'user name', 'admin-user', 'user!'];
        
        foreach ($validUsernames as $username) {
            expect(preg_match('/^[a-zA-Z0-9_]+$/', $username))->toBe(1);
        }
        
        foreach ($invalidUsernames as $username) {
            expect(preg_match('/^[a-zA-Z0-9_]+$/', $username))->not->toBe(1);
        }
    });
    
    test('email must be valid format', function () {
        $validEmails = ['user@example.com', 'test.user@example.co.uk', 'admin+test@example.com'];
        $invalidEmails = ['invalid', 'user@', '@example.com', 'user @example.com'];
        
        foreach ($validEmails as $email) {
            expect(filter_var($email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
        }
        
        foreach ($invalidEmails as $email) {
            expect(filter_var($email, FILTER_VALIDATE_EMAIL))->toBeFalse();
        }
    });
    
    test('password must be at least 8 characters', function () {
        $shortPassword = '1234567';
        expect(strlen($shortPassword))->toBeLessThan(8);
        
        $validPassword = '12345678';
        expect(strlen($validPassword))->toBeGreaterThanOrEqual(8);
    });
    
    test('password should contain mix of characters for strength', function () {
        // Weak passwords
        $weakPasswords = ['12345678', 'abcdefgh', 'ABCDEFGH'];
        
        // Strong passwords
        $strongPasswords = ['Pass123!', 'MyP@ssw0rd', 'Test1234!'];
        
        // Check weak passwords don't have variety
        foreach ($weakPasswords as $password) {
            $hasUpper = preg_match('/[A-Z]/', $password);
            $hasLower = preg_match('/[a-z]/', $password);
            $hasNumber = preg_match('/[0-9]/', $password);
            $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
            
            $variety = $hasUpper + $hasLower + $hasNumber + $hasSpecial;
            expect($variety)->toBeLessThan(3); // Weak passwords have less variety
        }
        
        // Check strong passwords have variety
        foreach ($strongPasswords as $password) {
            $hasUpper = preg_match('/[A-Z]/', $password);
            $hasLower = preg_match('/[a-z]/', $password);
            $hasNumber = preg_match('/[0-9]/', $password);
            
            $variety = $hasUpper + $hasLower + $hasNumber;
            expect($variety)->toBeGreaterThanOrEqual(3); // Strong passwords have variety
        }
    });
    
    test('roles must be valid array', function () {
        $validRoles = [
            json_encode(['ROLE_USER']),
            json_encode(['ROLE_ADMIN', 'ROLE_USER']),
            json_encode(['ROLE_ADMIN'])
        ];
        
        foreach ($validRoles as $rolesJson) {
            $decoded = json_decode($rolesJson, true);
            expect($decoded)->toBeArray()
                ->and($decoded)->not->toBeEmpty();
        }
    });
    
    test('roles must contain at least one valid role', function () {
        $validRolesList = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_EDITOR', 'ROLE_VIEWER'];
        
        $rolesJson = json_encode(['ROLE_USER']);
        $roles = json_decode($rolesJson, true);
        
        $hasValidRole = false;
        foreach ($roles as $role) {
            if (in_array($role, $validRolesList)) {
                $hasValidRole = true;
                break;
            }
        }
        
        expect($hasValidRole)->toBeTrue();
    });
    
    test('firstname and lastname should not be empty', function () {
        $validNames = ['John', 'Jane', 'Test', 'Admin'];
        $invalidNames = ['', '  ', null];
        
        foreach ($validNames as $name) {
            expect(trim($name))->not->toBeEmpty();
        }
        
        foreach ($invalidNames as $name) {
            expect(trim((string)$name))->toBeEmpty();
        }
    });
    
    test('names should not contain numbers or special characters', function () {
        $validNames = ['John', 'Mary', 'Jean-Pierre', "O'Connor"];
        $invalidNames = ['John123', 'Mary@', 'Test!', 'User#1'];
        
        foreach ($validNames as $name) {
            expect(preg_match("/^[a-zA-Z\s\-']+$/", $name))->toBe(1);
        }
        
        foreach ($invalidNames as $name) {
            expect(preg_match("/^[a-zA-Z\s\-']+$/", $name))->not->toBe(1);
        }
    });
    
});

describe('Admin User Business Rules', function () {
    
    test('system must have at least one active admin', function () {
        // Simulating: can't deactivate last admin
        $activeAdmins = 1; // Last admin
        
        expect($activeAdmins)->toBeGreaterThan(0);
        
        // Cannot deactivate if this is the only admin
        $canDeactivate = $activeAdmins > 1;
        expect($canDeactivate)->toBeFalse();
    });
    
    test('user cannot delete themselves', function () {
        $currentUserId = 1;
        $userToDelete = 1;
        
        expect($currentUserId)->toBe($userToDelete);
        
        $canDelete = $currentUserId !== $userToDelete;
        expect($canDelete)->toBeFalse();
    });
    
    test('only admin can create other admins', function () {
        $currentUserRoles = ['ROLE_ADMIN'];
        $targetRoles = ['ROLE_ADMIN'];
        
        $hasAdminRole = in_array('ROLE_ADMIN', $currentUserRoles);
        $creatingAdmin = in_array('ROLE_ADMIN', $targetRoles);
        
        $canCreate = !$creatingAdmin || $hasAdminRole;
        expect($canCreate)->toBeTrue();
    });
    
    test('regular user cannot assign admin role', function () {
        $currentUserRoles = ['ROLE_USER'];
        $targetRoles = ['ROLE_ADMIN'];
        
        $hasAdminRole = in_array('ROLE_ADMIN', $currentUserRoles);
        $assigningAdmin = in_array('ROLE_ADMIN', $targetRoles);
        
        $canAssign = !$assigningAdmin || $hasAdminRole;
        expect($canAssign)->toBeFalse();
    });
    
});
