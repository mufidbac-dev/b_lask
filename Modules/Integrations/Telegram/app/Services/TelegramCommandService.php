<?php

namespace Modules\Integrations\Telegram\Services;

use Modules\Personal\Models\Task;
use App\Models\Transaction;

class TelegramCommandService
{
    public function execute(int $userId, string $text): string
    {
        [$command, $argument] = array_pad(preg_split('/\s+/', trim($text), 2), 2, '');

        return match (strtolower($command)) {
            '/help' => "Commands:\n/task <title>\n/list\n/done <task id>",
            '/task' => $this->createTask($userId, $argument),
            '/list' => $this->listTasks($userId),
            '/done' => $this->completeTask($userId, $argument),
            '/rekap' => $this->summary($userId, $argument),
            default => 'Unknown command. Use /help.',
        };
    }

    private function createTask(int $userId, string $title): string
    {
        if ($title === '') {
            return 'Usage: /task <title>';
        }

        $task = Task::create(['user_id' => $userId, 'title' => $title]);

        return "Task #{$task->id} created.";
    }

    private function listTasks(int $userId): string
    {
        $tasks = Task::where('user_id', $userId)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->latest()
            ->limit(10)
            ->get(['id', 'title']);

        if ($tasks->isEmpty()) {
            return 'No open tasks.';
        }

        return $tasks->map(fn (Task $task): string => "#{$task->id} {$task->title}")->implode("\n");
    }

    private function completeTask(int $userId, string $id): string
    {
        if (! ctype_digit($id)) {
            return 'Usage: /done <task id>';
        }

        $updated = Task::where('user_id', $userId)->whereKey((int) $id)->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);

        return $updated === 1 ? "Task #{$id} completed." : 'Task not found.';
    }

    private function summary(int $userId, string $argument): string
    {
        $dates = preg_split('/\s+/', trim($argument));
        $from = $dates[0] ?? now()->startOfMonth()->toDateString();
        $to = $dates[1] ?? now()->toDateString();
        $rows = Transaction::where('user_id', $userId)->whereBetween('transaction_date', [$from, $to])
            ->with('category:id,name')->get()->groupBy(fn ($t) => $t->type);
        $income = (float) $rows->get('income', collect())->sum('amount');
        $expense = (float) $rows->get('expense', collect())->sum('amount');
        return "Rekap {$from} s/d {$to}\nPemasukan: Rp ".number_format($income, 0, ',', '.')."\nPengeluaran: Rp ".number_format($expense, 0, ',', '.');
    }
}
