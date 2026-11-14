{{--
    aftable::empty-state
    Shown when the table query returns zero rows.
    Blaze will fold this into the parent template at compile time.

    The icon used to fill with hardcoded hex (#F3F6F9/#7E8299/#FFA800) —
    fine under a light theme, but ui-flow (and any theme with a real dark
    mode) inverts its surface colours, and a hardcoded light-grey box stayed
    a hardcoded light-grey box regardless. currentColor at low opacity
    tracks whatever text colour the theme already applies to this cell.
--}}
@props(['message' => 'No data found'])

<tr>
    <td colspan="999" class="text-center align-middle py-5">
        <div class="d-flex flex-column align-items-center justify-content-center text-muted" style="min-height: 200px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                 style="opacity:.5" aria-hidden="true" focusable="false">
                <path d="M3 7h18M3 7v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7M3 7l2-4h14l2 4"/>
                <path d="M9 12h6"/>
            </svg>
            <div class="mt-3 fs-5">{{ $message }}</div>
        </div>
    </td>
</tr>
