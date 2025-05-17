<a wire:click="setAdmin({{ $item}})" class="admin-card" data-toggle="modal" data-target="#createEditAdmin" >
    <div class="circular-icon">
        <i class="fa-solid fa-user-gear"></i>
    </div>
    <div class="body">
        <div class="name">{{ ucfirst($item->fname) }} {{ ucfirst($item->lname) }}</div>
        <div class="email">{{ $item->email }}</div>
    </div>
</a>
