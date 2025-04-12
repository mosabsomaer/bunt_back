<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use App\Http\Requests\File\StoreFileRequest;
use App\Http\Requests\File\UpdateFileRequest;

class FileController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {}

    /**
     * Handle the error response.
     *
     * @param \Exception $e
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleError(\Exception $e, $statusCode = 500)
    {
        if ($e instanceof ModelNotFoundException) {
            $statusCode = 404;
            $error = 'File not found.';
        } elseif ($e instanceof ValidationException) {
            $statusCode = 422;
            $error = $e->errors();
        } else {
            $error = $e->getMessage();
        }

        return response()->json([
            'error' => $error
        ], $statusCode);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $files = $this->fileService->getAllFiles();
            return response()->json([
                'data' => $files
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    //thats how to name your file
    //you have to make sure that the file name doesnt contain "/" or it might cause you problems when you want to delete the file or retrive it
    public function store(StoreFileRequest $request)
    {
        try {
            $result = $this->fileService->storeFile($request->validated());
            return response()->json([
                'data' => 'Uploaded file on server',
                'order_id' => $result['order_id']
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $file = $this->fileService->getFile($id);
            return response()->json([
                'data' => $file
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    public function showByOrder(string $id)
    {
        try {
            $result = $this->fileService->getFilesByOrder($id);
            return response()->json([
                'data' => $result['files'],
                'number_pages' => $result['number_pages']
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFileRequest $request, string $id)
    {
        try {
            $result = $this->fileService->updateFile($id, $request->validated());
            return response()->json([
                'data' => 'Updated',
                'file' => $result['file'],
                'total number_pages for the order' => $result['total_pages']
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $result = $this->fileService->deleteFile($id);
            return response()->json([
                'data' => 'File Deleted',
                'file deleted from folder' => $result['storage_deleted'],
                'file path' => $result['file_path']
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    public function downloadFile(string $id)
    {
        try {
            $result = $this->fileService->downloadOrderFiles($id);
            
            $response = response()->download($result['zip_path'])->deleteFileAfterSend(true);
            $response->headers->set('total_price', $result['total_price']);
            
            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'total_price' => 0
            ], 404);
        }
    }
}
