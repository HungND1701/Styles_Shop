<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    /**
     * Tạo một phản hồi JSON chuẩn
     *
     * @param array $data
     * @param int $status
     * @return JsonResponse
     */
    protected function jsonResponse(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json($data, $status);
    }

    /**
     * Tạo một phản hồi lỗi JSON chuẩn
     *
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json(['error' => $message], $status);
    }

    /**
     * Xử lý yêu cầu không tìm thấy (404)
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Xử lý yêu cầu không được phép (403)
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Xử lý yêu cầu thành công với thông báo (200)
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function successResponse(string $message = 'Success'): JsonResponse
    {
        return $this->jsonResponse(['message' => $message], Response::HTTP_OK);
    }

    /**
     * Xử lý yêu cầu tạo mới thành công (201)
     *
     * @param string $message
     * @param array $data
     * @return JsonResponse
     */
    protected function createdResponse(string $message = 'Resource created', array $data = []): JsonResponse
    {
        return $this->jsonResponse(['message' => $message, 'data' => $data], Response::HTTP_CREATED);
    }

    /**
     * Xử lý yêu cầu cập nhật thành công (200)
     *
     * @param string $message
     * @param array $data
     * @return JsonResponse
     */
    protected function updatedResponse(string $message = 'Resource updated', array $data = []): JsonResponse
    {
        return $this->jsonResponse(['message' => $message, 'data' => $data], Response::HTTP_OK);
    }

    /**
     * Xử lý yêu cầu xóa thành công (204)
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function deletedResponse(string $message = 'Resource deleted'): JsonResponse
    {
        return $this->jsonResponse(['message' => $message], Response::HTTP_NO_CONTENT);
    }
}
