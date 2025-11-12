<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Support\UploadHandler;

/**
 * @internal
 *
 * @covers \WebFramework\Support\UploadHandler
 */
final class UploadHandlerTest extends Unit
{
    protected function _before()
    {
        $_FILES = [];
    }

    protected function _after()
    {
        $_FILES = [];
    }

    public function testCheckUploadNoFilePresent()
    {
        $handler = new UploadHandler('test_file');
        $result = $handler->checkUpload(1000);

        verify($result)->equals('no_file_present');
    }

    public function testCheckUploadFileSizeZero()
    {
        $_FILES['test_file'] = [
            'size' => 0,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/test',
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $result = $handler->checkUpload(1000);

        verify($result)->equals('no_file_present');
    }

    public function testCheckUploadError()
    {
        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_PARTIAL,
            'tmp_name' => '/tmp/test',
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $result = $handler->checkUpload(1000);

        verify($result)->equals('upload_error');
    }

    public function testCheckUploadErrorArray()
    {
        $_FILES['test_file'] = [
            'size' => 100,
            'error' => [UPLOAD_ERR_OK],
            'tmp_name' => '/tmp/test',
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $result = $handler->checkUpload(1000);

        verify($result)->equals('upload_error');
    }

    public function testCheckUploadFileTooLarge()
    {
        $_FILES['test_file'] = [
            'size' => 2000,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/test',
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $result = $handler->checkUpload(1000);

        verify($result)->equals('file_too_large');
    }

    public function testCheckUploadMimeTypeNotAllowed()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content');

        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $result = $handler->checkUpload(1000, ['image/jpeg', 'image/png']);

        verify($result)->equals('mime_type_not_allowed');

        unlink($tempFile);
    }

    public function testCheckUploadSuccess()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content');

        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $result = $handler->checkUpload(1000, true);

        verify($result)->equals(true);

        unlink($tempFile);
    }

    public function testGetExtensionJpeg()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');

        $jpegHeader = "\xFF\xD8\xFF\xE0\x00\x10JFIF";
        file_put_contents($tempFile, $jpegHeader.str_repeat('x', 100));

        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'test.jpg',
        ];

        $handler = new UploadHandler('test_file');
        $handler->checkUpload(1000, ['image/jpeg']);

        $extension = $handler->getExtension();
        verify($extension)->equals('jpg');

        unlink($tempFile);
    }

    public function testGetExtensionUnknown()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'unknown content');

        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'test.unknown',
        ];

        $handler = new UploadHandler('test_file');
        $handler->checkUpload(1000, true);

        $extension = $handler->getExtension();
        verify($extension)->equals(false);

        unlink($tempFile);
    }

    public function testGetMimeType()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content');

        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $handler->checkUpload(1000, true);

        $mimeType = $handler->getMimeType();
        verify($mimeType)->notEmpty();

        unlink($tempFile);
    }

    public function testGetOrigFilename()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content');

        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'original_filename.txt',
        ];

        $handler = new UploadHandler('test_file');
        $handler->checkUpload(1000, true);

        verify($handler->getOrigFilename())->equals('original_filename.txt');

        unlink($tempFile);
    }

    public function testGetTmpFilename()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content');

        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $handler->checkUpload(1000, true);

        verify($handler->getTmpFilename())->equals($tempFile);

        unlink($tempFile);
    }

    public function testMoveSuccess()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        $targetFile = tempnam(sys_get_temp_dir(), 'upload_target');
        unlink($targetFile);

        file_put_contents($tempFile, 'test content');

        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $handler->checkUpload(1000, true);

        $result = $handler->move($targetFile);

        verify($result)->equals('save_failed');

        if (file_exists($tempFile))
        {
            unlink($tempFile);
        }
        if (file_exists($targetFile))
        {
            unlink($targetFile);
        }
    }

    public function testCheckUploadWithWhitelistArray()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content');

        $_FILES['test_file'] = [
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $result = $handler->checkUpload(1000, ['text/plain']);

        verify($result)->equals(true);

        unlink($tempFile);
    }

    public function testCheckUploadMaxSizeBoundary()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, str_repeat('x', 1000));

        $_FILES['test_file'] = [
            'size' => 1000,
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $tempFile,
            'name' => 'test.txt',
        ];

        $handler = new UploadHandler('test_file');
        $result = $handler->checkUpload(1000, true);

        verify($result)->equals(true);

        unlink($tempFile);
    }
}
