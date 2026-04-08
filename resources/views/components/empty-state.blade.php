{{--
    aftable::empty-state
    Shown when the table query returns zero rows.
    Blaze will fold this into the parent template at compile time.
--}}
@props(['message' => 'No data found'])

<tr>
    <td colspan="999" class="text-center align-middle py-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 200px;">
            <svg width="64" height="64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="64" height="64" rx="12" fill="#F3F6F9"/>
                <path d="M20 28h24v16H20z" fill="#7E8299"/>
                <path d="M20 20h8l4 4h12a2 2 0 0 1 2 2v18a2 2 0 0 1-2 2H20a2 2 0 0 1-2-2V22a2 2 0 0 1 2-2z" fill="#FFA800"/>
            </svg>
            <div class="mt-3 fs-5 text-muted">{{ $message }}</div>
        </div>
    </td>
</tr>
