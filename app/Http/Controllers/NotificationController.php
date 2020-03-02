<?php

namespace App\Http\Controllers;

use App\Exceptions\ShouldDeleteNotificationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class NotificationController extends Controller {
    
    public function latest() {
        return Auth::user()->notifications
            ->take(10)
            ->map(function($notification) {
                try {
                    $notification->html = $notification->type::render($notification);
                    return $notification;
                } catch (ShouldDeleteNotificationException $e) {
                    $notification->delete();
                    return null;
                }
            })
            // We don't need empty notifications
            ->filter(function($notification_or_null) { return $notification_or_null != null; })
            ->values();
    }

    public static function toggleReadState($id) {
        $notification = Auth::user()->notifications->where('id', $id)->first();
        
        // Might have cached the html property and would then try to shove it in the DB, mostly
        // happened during tests.
        if(isset($notification->html)) unset($notification->html);

        if($notification->read_at == null) { // old state = unread
            $notification->markAsRead();
            return Response::json($notification, 201); // new state = read, 201=created
        } else { // old state = read
            $notification->markAsUnread();
            return Response::json($notification, 202); // new state = unread, 202=accepted
        }
    }

    public function readAll() {
        Auth::user()->unreadNotifications->markAsRead();
    }
}