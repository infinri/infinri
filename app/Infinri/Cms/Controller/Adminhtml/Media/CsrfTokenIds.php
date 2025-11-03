<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

final class CsrfTokenIds
{
    public const UPLOAD = 'admin_media_upload';
    public const CREATE_FOLDER = 'admin_media_create_folder';
    public const DELETE = 'admin_media_delete';

    private function __construct() {}
}
