# 05 — Media & Storage

## 12. Media & storage architecture

Media never touches the relational DB — only a `media` row (path, mime, size, checksum, status) points at an object in R2. CDN serves reads.

**Upload flow:** client requests a **signed, short-lived upload URL** → uploads directly to R2 → a queued job validates and finalizes → `media.status` goes `pending → ready` (or `rejected`).

- **Validation:** enforce size/count limits server-side; check MIME **and** file signature (magic bytes), not just extension; re-encode images to strip metadata/exploits; reject on mismatch.
- **Limits:** account images — max 5, 5 MB each. Base — max 2 images (5 MB each) + max 1 video (~30–60 s, ~50–100 MB, post-MVP).
- **Malware:** treat uploads as untrusted; scan (ClamAV worker or provider); serve from a cookieless CDN domain; never execute; force download-safe content types.
- **Access:** public content via CDN; private/pending media via signed, expiring URLs only.
- **Thumbnails:** generated in a worker on finalize; derivatives stored as separate `media` rows linked to the original.
- **Video (post-MVP):** transcode + thumbnail via ffmpeg worker; enforce duration/size before publish; consider a managed provider at volume.
- **Cleanup:** orphaned-file reaper — scheduled job deletes R2 objects with no `media` row or `status=pending` past TTL; deleting a base/account enqueues media deletion; every delete idempotent.
