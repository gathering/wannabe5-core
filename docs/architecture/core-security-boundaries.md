# Native API security boundaries

Native controllers enforce authorization on both primary paths and frontend aliases. Compatibility adapters continue to delegate authentication and authorization upstream.

- Access administration requires `access:owner`; users cannot grant permissions merely because they own the target account. Reading arbitrary attributes requires access-reader or higher permissions.
- Applications belong to their authenticated applicant. Creation always starts at `received`. Crew application managers authorize decisions, including status changes submitted through the general update endpoint. Answers require applicant ownership; reviewer comments require crew application management.
- Profile aliases enforce the core ownership policy, including explicit `profile:owner` delegates. Search returns only profiles the actor may read. Image moderation requires global image-manager or event-management permission. The existing image PUT accepts moderation fields only for those actors. Image owners cannot self-approve.
- Crew membership/team writes require scoped crew or event management. Related roles and teams must belong to the selected event/crew. Event readers cannot edit events.
- Task submissions require actor ownership and assignment. Approval requires task-approver or event-owner permission. Crew response lists include only members of that crew.
- Global core pages lack event scope: creation requires wildcard event management; authors or wildcard event managers may edit. Private pages are visible only to authors and global managers. Version history requires edit permission. Authenticated users may read public pages. Authorship is derived from the actor on creation and preserved on edits.
- Communication writes require communication administration/management or list administration/management, as appropriate. Reads require corresponding reader permissions. Delivery state is server-controlled.

## Authentication

OIDC callbacks bind the user ID to the server session and regenerate its identifier. JSON and redirect login initiation persist the same state they return. Cookie renewal, including `after-login`, requires that bound session and a successful upstream refresh. A copied JWT alone cannot renew itself. Ordinary SSR API reads continue to accept the existing JWT cookie.

## Resource and delivery limits

Uploads retain the 10 MB limit and additionally reject images above 20 million pixels before decoding. Crop coordinates and sizes must be finite normalized numbers within the image.

Delivery resolves workflow user IDs to profile email addresses. Forced log delivery selects the log mailer explicitly. A database transaction locks the message row through delivery, preventing concurrent workers from sending it simultaneously. Failure state is committed before rethrowing for queue retry. Database row locks release automatically on worker failure; SMTP can still duplicate a message if sending succeeds immediately before a crash prevents recording success. No claim of exactly-once external delivery is made.

Compatibility forwarding preserves upstream redirects rather than following them.

## Deferred coordinated work

CSRF token integration, POST-only retry/logout, separate moderation endpoints, removal of service-login fallback tokens, and JWT issuer/audience enforcement remain follow-ups. This release preserves existing frontend request contracts. Authorization denials and resource limits are intentional security differences from the initial native implementation.
