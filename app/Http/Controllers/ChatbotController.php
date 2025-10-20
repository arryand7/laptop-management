<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatbotCommitRequest;
use App\Http\Requests\ChatbotPreviewRequest;
use App\Support\Chatbot\AiInsightsService;
use App\Support\Chatbot\CommandParser;
use App\Support\Chatbot\CommitService;
use App\Support\Chatbot\PreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ChatbotController extends Controller
{
    public function __construct(
        private PreviewService $previewService,
        private CommitService $commitService,
        private AiInsightsService $aiInsightsService,
    )
    {
    }

    public function index(Request $request): View
    {
        return view('chatbot.index');
    }

    public function preview(ChatbotPreviewRequest $request): JsonResponse
    {
        $commandText = $request->validated()['command'];

        try {
            $parsed = CommandParser::parse($commandText);
        } catch (InvalidArgumentException $e) {
            $lower = strtolower(trim($commandText));
            if (str_starts_with($lower, 'pinjam') || str_starts_with($lower, 'kembalikan')) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            $analysis = $this->aiInsightsService->respond($commandText);

            return response()->json([
                'status' => 'analysis',
                'reply' => $analysis['reply'],
                'suggestions' => $analysis['suggestions'],
            ]);
        }

        try {
            $preview = $this->previewService->build($request->user(), $parsed);

            return response()->json($preview);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function commit(ChatbotCommitRequest $request): JsonResponse
    {
        try {
            $result = $this->commitService->commit($request->user(), $request->validated()['token']);

            return response()->json([
                'status' => 'ok',
                'result' => $result,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
