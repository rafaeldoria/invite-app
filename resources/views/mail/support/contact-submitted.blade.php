New support request received.

Name:
{{ $supportContact->name }}

Contact:
{{ $supportContact->contact }}

Subject:
{{ $supportContact->subject }}

Message:
{{ $supportContact->message }}

Submitted at:
{{ $supportContact->created_at?->toISOString() }}
