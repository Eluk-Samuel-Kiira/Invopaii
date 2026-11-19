<style>
.api-layout{display:grid;grid-template-columns:220px minmax(0,1fr);gap:56px;padding:56px 0 120px;align-items:start}
.api-side{position:sticky;top:20px;align-self:start;font-size:14px;line-height:1.4;max-height:calc(100vh - 40px);overflow-y:auto;padding-right:8px}
.api-side::-webkit-scrollbar{width:4px}
.api-side::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:4px}
.api-nav-group{font-size:11.5px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--accent-soft);margin:22px 0 6px}
.api-nav-group:first-of-type{margin-top:0}
.api-side a{display:block;padding:5px 0;color:var(--muted);text-decoration:none;font-size:14px;transition:color .1s ease}
.api-side a:hover{color:#fff}
.api-main{min-width:0;font-size:15.5px;line-height:1.7;color:#fff}
.api-main p{color:var(--muted);margin:12px 0}
.api-main h2{font-family:var(--display);font-size:22px;font-weight:700;letter-spacing:-.01em;margin:56px 0 14px;padding-top:24px;border-top:1px solid var(--line);color:#fff;scroll-margin-top:24px}
.api-main h2:first-of-type{border-top:0;padding-top:0;margin-top:24px}
.api-main h3{font-family:var(--display);font-size:16.5px;font-weight:600;margin:28px 0 8px;color:#fff}
.api-main code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.9em;background:rgba(255,255,255,.08);padding:2px 6px;border-radius:5px;color:#E7DDFF}
.api-main a{color:var(--accent-soft)}
.api-main a:hover{color:#fff}
.api-callout{padding:14px 18px;border-left:3px solid var(--violet);background:rgba(110,63,231,.12);border-radius:0 8px 8px 0;margin:20px 0;font-size:14.5px;color:var(--muted)}
.api-callout strong{color:#fff}
.api-callout.warn{border-left-color:#f59e0b;background:rgba(245,158,11,.1)}
.api-code{background:#160C2B;border:1px solid rgba(255,255,255,.08);border-radius:12px;overflow:hidden;margin:16px 0}
.api-code pre{margin:0;padding:18px 20px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;line-height:1.65;overflow-x:auto;color:#D9CDF5}
.api-code .k{color:#FF9E64}
.api-code .s{color:#9BE7A8}
.api-code .c{color:#8574B5;font-style:italic}
.api-code .n{color:#7FD3FF}
.api-table{width:100%;border-collapse:collapse;margin:18px 0;font-size:14px}
.api-table th,.api-table td{padding:10px 14px;text-align:left;border-bottom:1px solid var(--line);vertical-align:top}
.api-table th{font-weight:600;color:var(--accent-soft);font-size:12px;letter-spacing:.06em;text-transform:uppercase;background:rgba(255,255,255,.02)}
.api-table td{color:var(--muted)}
.api-table td code{color:#E7DDFF;background:rgba(110,63,231,.18)}
.api-table td strong{color:#fff}
.api-method{display:inline-block;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11.5px;font-weight:700;letter-spacing:.03em;padding:3px 8px;border-radius:5px;margin-right:10px;vertical-align:middle}
.api-method--post{background:#0E3D24;color:#7EE3A8}
.api-method--get{background:#0A2D46;color:#7FD3FF}
.api-method--put{background:#3D2E0E;color:#FFD37F}
.api-method--delete{background:#3D0E1A;color:#FF9EB5}
@media (max-width:900px){
  .api-layout{grid-template-columns:1fr;gap:28px;padding:32px 0 80px}
  .api-side{position:static;max-height:none;border:1px solid var(--line);border-radius:14px;padding:20px;background:rgba(255,255,255,.02)}
  .api-main h2{font-size:19px;margin:40px 0 12px;padding-top:20px}
  .api-table{font-size:13px}
  .api-table th,.api-table td{padding:8px 10px}
}
</style>