<?php

namespace Tests\Feature;

use App\Events\ChatMessageSent;
use App\Models\ChatRoom;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Phase4ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_member_cannot_post_message(): void
    {
        $a = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $b = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $c = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $g = Group::create(['name' => 'G', 'description' => null, 'created_by' => null, 'updated_by' => null]);
        $a->groups()->attach($g->id);
        $b->groups()->attach($g->id);
        $c->groups()->attach($g->id);

        $room = ChatRoom::create(['name' => null, 'type' => ChatRoom::TYPE_DIRECT]);
        $room->users()->sync([$a->id, $b->id]);

        $this->actingAs($c);
        $this->postJson("/api/chat/rooms/{$room->id}/messages", [
            'body' => 'hack',
        ])->assertForbidden();
    }

    public function test_member_can_post_in_direct_room(): void
    {
        $a = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $b = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $g = Group::create(['name' => 'Gx', 'description' => null, 'created_by' => null, 'updated_by' => null]);
        $a->groups()->attach($g->id);
        $b->groups()->attach($g->id);

        $this->actingAs($a);
        $this->postJson('/api/chat/rooms', [
            'type' => ChatRoom::TYPE_DIRECT,
            'participant_user_id' => $b->id,
        ])->assertCreated();

        $room = ChatRoom::query()->where('type', ChatRoom::TYPE_DIRECT)->firstOrFail();
        $this->postJson("/api/chat/rooms/{$room->id}/messages", [
            'body' => 'hello',
        ])->assertCreated();

        $this->assertDatabaseHas('chat_messages', [
            'chat_room_id' => $room->id,
            'user_id' => $a->id,
        ]);
    }

    public function test_message_post_dispatches_chat_message_sent_event(): void
    {
        Event::fake([ChatMessageSent::class]);

        $a = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $b = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $g = Group::create(['name' => 'Gx', 'description' => null, 'created_by' => null, 'updated_by' => null]);
        $a->groups()->attach($g->id);
        $b->groups()->attach($g->id);

        $this->actingAs($a);
        $this->postJson('/api/chat/rooms', [
            'type' => ChatRoom::TYPE_DIRECT,
            'participant_user_id' => $b->id,
        ])->assertCreated();

        $room = ChatRoom::query()->where('type', ChatRoom::TYPE_DIRECT)->firstOrFail();
        $this->postJson("/api/chat/rooms/{$room->id}/messages", [
            'body' => 'hello',
        ])->assertCreated();

        Event::assertDispatched(ChatMessageSent::class, function (ChatMessageSent $event) use ($room): bool {
            return $event->message->chat_room_id === $room->id
                && $event->message->body === 'hello';
        });
    }

    public function test_non_member_post_does_not_dispatch_chat_message_sent(): void
    {
        Event::fake([ChatMessageSent::class]);

        $a = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $b = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $c = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $g = Group::create(['name' => 'G', 'description' => null, 'created_by' => null, 'updated_by' => null]);
        $a->groups()->attach($g->id);
        $b->groups()->attach($g->id);
        $c->groups()->attach($g->id);

        $room = ChatRoom::create(['name' => null, 'type' => ChatRoom::TYPE_DIRECT]);
        $room->users()->sync([$a->id, $b->id]);

        $this->actingAs($c);
        $this->postJson("/api/chat/rooms/{$room->id}/messages", [
            'body' => 'hack',
        ])->assertForbidden();

        Event::assertNotDispatched(ChatMessageSent::class);
    }
}
