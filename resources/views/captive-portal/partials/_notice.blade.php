@if($portal?->notice_title || $portal?->notice_body)
    <div class="notice">
        @if($portal->notice_title)<div class="t">{{ $portal->notice_title }}</div>@endif
        @if($portal->notice_body)<div class="b">{{ $portal->notice_body }}</div>@endif
    </div>
@endif

@foreach($announcements ?? [] as $announcement)
    <div class="notice">
        <div class="t">{{ ucfirst($announcement->category) }}</div>
        <div class="b">{{ $announcement->message }}</div>
    </div>
@endforeach
