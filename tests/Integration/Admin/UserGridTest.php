<?php

use Infinri\Admin\Ui\Component\Listing\DataProvider;
use Infinri\Admin\Ui\Component\Listing\Column\UserActions;
use Infinri\Admin\Model\ResourceModel\AdminUser;
use Infinri\Core\Model\ObjectManager;

beforeEach(function () {
    // ObjectManager is initialized by Pest.php for Integration tests
    $this->objectManager = ObjectManager::getInstance();
});

describe('Admin User Grid Components', function () {
    
    test('AdminUser resource returns users from database', function () {
        $adminUserResource = $this->objectManager->get(AdminUser::class);
        $users = $adminUserResource->findAll();
        
        expect($users)->toBeArray()
            ->and($users)->not->toBeEmpty()
            ->and($users[0])->toHaveKey('user_id')
            ->and($users[0])->toHaveKey('username')
            ->and($users[0])->toHaveKey('email');
    });
    
    test('DataProvider formats user data correctly', function () {
        $dataProvider = $this->objectManager->get(DataProvider::class);
        
        $data = $dataProvider->getData();
        
        expect($data)->toHaveKey('items')
            ->and($data)->toHaveKey('totalRecords')
            ->and($data['items'])->toBeArray()
            ->and($data['items'])->not->toBeEmpty()
            ->and($data['totalRecords'])->toBeGreaterThan(0);
        
        $firstItem = $data['items'][0];
        expect($firstItem)->toHaveKey('user_id')
            ->and($firstItem)->toHaveKey('username')
            ->and($firstItem)->toHaveKey('email')
            ->and($firstItem)->toHaveKey('roles')
            ->and($firstItem)->toHaveKey('is_active');
    });
    
    test('UserActions adds edit and delete actions to items', function () {
        $userActions = new UserActions();
        
        $testData = [
            'data' => [
                'items' => [
                    [
                        'user_id' => 1,
                        'username' => 'admin',
                        'email' => 'admin@test.com'
                    ]
                ]
            ],
            'totalRecords' => 1
        ];
        
        $result = $userActions->prepareDataSource($testData);
        
        expect($result['data']['items'][0])->toHaveKey('actions')
            ->and($result['data']['items'][0]['actions'])->toHaveKey('edit')
            ->and($result['data']['items'][0]['actions'])->toHaveKey('delete')
            ->and($result['data']['items'][0]['actions']['edit'])->toHaveKey('href')
            ->and($result['data']['items'][0]['actions']['edit'])->toHaveKey('label')
            ->and($result['data']['items'][0]['actions']['delete'])->toHaveKey('href')
            ->and($result['data']['items'][0]['actions']['delete'])->toHaveKey('label')
            ->and($result['data']['items'][0]['actions']['delete'])->toHaveKey('confirm');
    });
    
    test('UserActions generates correct URLs for edit and delete', function () {
        $userActions = new UserActions();
        
        $testData = [
            'data' => [
                'items' => [
                    ['user_id' => 123]
                ]
            ]
        ];
        
        $result = $userActions->prepareDataSource($testData);
        $actions = $result['data']['items'][0]['actions'];
        
        expect($actions['edit']['href'])->toBe('/admin/users/edit?id=123')
            ->and($actions['delete']['href'])->toBe('/admin/users/delete?id=123')
            ->and($actions['edit']['label'])->toBe('Edit')
            ->and($actions['delete']['label'])->toBe('Delete');
    });
    
    test('UserActions handles multiple users', function () {
        $userActions = new UserActions();
        
        $testData = [
            'data' => [
                'items' => [
                    ['user_id' => 1, 'username' => 'admin1'],
                    ['user_id' => 2, 'username' => 'admin2'],
                    ['user_id' => 3, 'username' => 'admin3']
                ]
            ]
        ];
        
        $result = $userActions->prepareDataSource($testData);
        
        expect($result['data']['items'])->toHaveCount(3);
        
        foreach ($result['data']['items'] as $item) {
            expect($item)->toHaveKey('actions')
                ->and($item['actions'])->toHaveKey('edit')
                ->and($item['actions'])->toHaveKey('delete');
        }
    });
    
});
