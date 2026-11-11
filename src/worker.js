/**
 * Cloudflare Worker لتشغيل منصة Ju File كاملة على شبكة Cloudflare ونطاقك الخاص
 * عند النشر عبر GitHub (`npx wrangler deploy`).
 */

const APP_NAME = 'Ju File';
const MAX_UPLOAD_BYTES = 50 * 1024 * 1024;

const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];
const VIDEO_EXT = ['mp4', 'webm', 'mov', 'mkv', 'avi', 'm4v', '3gp'];
const AUDIO_EXT = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'opus', 'wma'];
const TEXT_EXT  = ['txt', 'csv', 'json', 'md', 'html', 'xml', 'log', 'srt'];

const START_FIELD = { name: 'start', label: 'البداية (ثانية)', type: 'number', min: 0, step: 0.1, value: 0, media_time: true };
const END_FIELD   = { name: 'end', label: 'النهاية (ثانية)', type: 'number', min: 0.1, step: 0.1, media_time: true };

const CATEGORIES = {
  image: {
    title: 'الصور', icon: 'bi-image', page: 'image.php',
    desc: 'صغّر حجم صورك، غيّر أبعادها، قصّها، دوّرها، أو حوّل صيغتها.',
    tools: {
      compress: {
        title: 'تصغير الحجم', icon: 'bi-arrows-collapse', engine: 'server', accept: IMAGE_EXT,
        desc: 'قلّل حجم الصورة مع جودة مقبولة.',
        fields: [
          { name: 'quality', label: 'الجودة', type: 'range', min: 10, max: 95, value: 70, suffix: '%' },
          { name: 'format', label: 'صيغة الناتج', type: 'select', value: 'keep', options: { keep: 'نفس الصيغة', jpg: 'JPG', webp: 'WebP (الأصغر)' } },
          { name: 'max_width', label: 'أقصى عرض بالبكسل (اختياري)', type: 'number', min: 16, max: 10000, required: false, placeholder: 'بدون تغيير' },
        ],
      },
      resize: {
        title: 'تغيير الأبعاد', icon: 'bi-aspect-ratio', engine: 'server', accept: IMAGE_EXT,
        desc: 'حدد العرض أو الارتفاع الجديد.',
        fields: [
          { name: 'width', label: 'العرض (px)', type: 'number', min: 1, max: 10000, required: false },
          { name: 'height', label: 'الارتفاع (px)', type: 'number', min: 1, max: 10000, required: false },
          { name: 'keep_ratio', label: 'حافظ على تناسب الأبعاد', type: 'checkbox', value: true },
        ],
      },
      crop: {
        title: 'قص (Crop)', icon: 'bi-crop', engine: 'server', accept: IMAGE_EXT, crop_ui: true,
        desc: 'اسحب على المعاينة لتحديد المنطقة، أو اكتب القيم.',
        fields: [
          { name: 'x', label: 'من اليسار X', type: 'number', min: 0, max: 20000, value: 0 },
          { name: 'y', label: 'من الأعلى Y', type: 'number', min: 0, max: 20000, value: 0 },
          { name: 'width', label: 'العرض', type: 'number', min: 1, max: 20000 },
          { name: 'height', label: 'الارتفاع', type: 'number', min: 1, max: 20000 },
        ],
      },
      rotate: {
        title: 'تدوير وقلب', icon: 'bi-arrow-clockwise', engine: 'server', accept: IMAGE_EXT,
        desc: 'دوّر الصورة أو اقلبها.',
        fields: [
          { name: 'angle', label: 'التدوير', type: 'select', value: '90', options: { '0': 'بدون', '90': '90° مع عقارب الساعة', '180': '180°', '270': '90° عكس عقارب الساعة' } },
          { name: 'flip', label: 'القلب', type: 'select', value: 'none', options: { none: 'بدون', h: 'أفقي (مرآة)', v: 'عمودي' } },
        ],
      },
      convert: {
        title: 'تحويل الصيغة', icon: 'bi-arrow-left-right', engine: 'server', accept: IMAGE_EXT,
        desc: 'حوّل بين JPG و PNG و WebP و GIF و BMP.',
        fields: [
          { name: 'format', label: 'الصيغة الجديدة', type: 'select', value: 'png', options: { jpg: 'JPG', png: 'PNG', webp: 'WebP', gif: 'GIF', bmp: 'BMP' } },
        ],
      },
      filter: {
        title: 'فلاتر', icon: 'bi-magic', engine: 'server', accept: IMAGE_EXT,
        desc: 'أبيض وأسود، سيبيا، تمويه وغيرها.',
        fields: [
          { name: 'effect', label: 'التأثير', type: 'select', value: 'grayscale', options: { grayscale: 'أبيض وأسود', sepia: 'سيبيا (قديم)', negate: 'عكس الألوان', blur: 'تمويه', sharpen: 'زيادة الحدة', brighten: 'زيادة الإضاءة' } },
        ],
      },
    },
  },
  video: {
    title: 'الفيديو', icon: 'bi-camera-video', page: 'video.php',
    desc: 'صغّر حجم الفيديو، قصّ جزءاً منه، اقتصّ الإطار، أو حوّل صيغته — داخل متصفحك.',
    tools: {
      compress: {
        title: 'تصغير الحجم', icon: 'bi-arrows-collapse', engine: 'ffmpeg', accept: VIDEO_EXT,
        desc: 'ضغط الفيديو إلى MP4 بحجم أصغر.',
        fields: [
          { name: 'quality', label: 'مستوى الضغط', type: 'select', value: '28', options: { '23': 'خفيف (جودة عالية)', '28': 'متوسط (موصى به)', '33': 'قوي (أصغر حجم)' } },
          { name: 'max_height', label: 'أقصى دقة', type: 'select', value: '720', options: { '0': 'بدون تغيير', '1080': '1080p', '720': '720p', '480': '480p', '360': '360p' } },
        ],
      },
      trim: {
        title: 'قص المدة', icon: 'bi-scissors', engine: 'ffmpeg', accept: VIDEO_EXT,
        desc: 'احتفظ بجزء محدد من الفيديو.',
        fields: [START_FIELD, END_FIELD, { name: 'fast', label: 'قص سريع بدون إعادة ترميز (أقل دقة)', type: 'checkbox', value: true }],
      },
      crop: {
        title: 'اقتصاص الإطار', icon: 'bi-crop', engine: 'ffmpeg', accept: VIDEO_EXT,
        desc: 'غيّر أبعاد الإطار (مثلاً لستوري أو ريلز).',
        fields: [
          { name: 'ratio', label: 'النسبة', type: 'select', value: '9:16', options: { '9:16': '9:16 (ستوري / ريلز)', '1:1': '1:1 (مربع)', '4:5': '4:5 (منشور)', '16:9': '16:9 (عريض)', '4:3': '4:3' } },
        ],
      },
      convert: {
        title: 'تحويل الصيغة', icon: 'bi-arrow-left-right', engine: 'ffmpeg', accept: VIDEO_EXT,
        desc: 'MP4، WebM، MOV، MKV، AVI أو GIF متحرك.',
        fields: [
          { name: 'format', label: 'الصيغة الجديدة', type: 'select', value: 'mp4', options: { mp4: 'MP4', webm: 'WebM', mov: 'MOV', mkv: 'MKV', avi: 'AVI', gif: 'GIF متحرك (أول 15 ثانية)' } },
        ],
      },
      extract_audio: {
        title: 'استخراج الصوت', icon: 'bi-music-note', engine: 'ffmpeg', accept: VIDEO_EXT,
        desc: 'احفظ صوت الفيديو كملف مستقل.',
        fields: [
          { name: 'format', label: 'صيغة الصوت', type: 'select', value: 'mp3', options: { mp3: 'MP3', wav: 'WAV', m4a: 'M4A' } },
        ],
      },
      mute: {
        title: 'كتم الصوت', icon: 'bi-volume-mute', engine: 'ffmpeg', accept: VIDEO_EXT,
        desc: 'أزل الصوت من الفيديو.', fields: [],
      },
    },
  },
  audio: {
    title: 'الصوت', icon: 'bi-music-note-beamed', page: 'audio.php',
    desc: 'حوّل صيغة ملفاتك الصوتية، صغّر حجمها، قصّها أو عدّل مستوى الصوت.',
    tools: {
      convert: {
        title: 'تحويل الصيغة', icon: 'bi-arrow-left-right', engine: 'ffmpeg', accept: AUDIO_EXT,
        desc: 'MP3، WAV، OGG، M4A أو FLAC.',
        fields: [
          { name: 'format', label: 'الصيغة الجديدة', type: 'select', value: 'mp3', options: { mp3: 'MP3', wav: 'WAV', ogg: 'OGG', m4a: 'M4A', flac: 'FLAC' } },
        ],
      },
      compress: {
        title: 'تصغير الحجم', icon: 'bi-arrows-collapse', engine: 'ffmpeg', accept: AUDIO_EXT,
        desc: 'خفّض معدل البت للحصول على ملف MP3 أصغر.',
        fields: [
          { name: 'bitrate', label: 'معدل البت', type: 'select', value: '96', options: { '128': '128k (جيد)', '96': '96k (متوسط)', '64': '64k (صغير)', '32': '32k (صوت كلام)' } },
          { name: 'mono', label: 'تحويل إلى أحادي (Mono)', type: 'checkbox', value: false },
        ],
      },
      trim: {
        title: 'قص المدة', icon: 'bi-scissors', engine: 'ffmpeg', accept: AUDIO_EXT,
        desc: 'احتفظ بجزء محدد من الملف الصوتي.', fields: [START_FIELD, END_FIELD],
      },
      volume: {
        title: 'مستوى الصوت', icon: 'bi-volume-up', engine: 'ffmpeg', accept: AUDIO_EXT,
        desc: 'ارفع أو اخفض الصوت، أو اجعله متوازناً.',
        fields: [
          { name: 'level', label: 'المستوى', type: 'select', value: '1.5', options: { '0.5': '50%', '0.75': '75%', '1.25': '125%', '1.5': '150%', '2': '200%', normalize: 'موازنة تلقائية' } },
        ],
      },
    },
  },
  document: {
    title: 'المستندات', icon: 'bi-file-earmark-text', page: 'document.php',
    desc: 'أدوات PDF، تحويل الملفات النصية، تنظيفها، وضغط الملفات في ZIP.',
    tools: {
      pdf_merge: { title: 'دمج PDF', icon: 'bi-union', engine: 'pdf', accept: ['pdf'], multi: true, desc: 'اجمع عدة ملفات PDF في ملف واحد بالترتيب.', fields: [] },
      pdf_extract: {
        title: 'استخراج صفحات', icon: 'bi-file-earmark-break', engine: 'pdf', accept: ['pdf'],
        desc: 'أنشئ PDF جديداً من صفحات محددة.',
        fields: [{ name: 'pages', label: 'الصفحات (مثال: 1-3,5)', type: 'text', placeholder: '1-3,5' }],
      },
      pdf_delete: {
        title: 'حذف صفحات', icon: 'bi-file-earmark-x', engine: 'pdf', accept: ['pdf'],
        desc: 'احذف صفحات لا تحتاجها.',
        fields: [{ name: 'pages', label: 'الصفحات المحذوفة (مثال: 2,4-6)', type: 'text', placeholder: '2,4-6' }],
      },
      pdf_rotate: {
        title: 'تدوير PDF', icon: 'bi-arrow-clockwise', engine: 'pdf', accept: ['pdf'],
        desc: 'دوّر كل الصفحات أو صفحات محددة.',
        fields: [
          { name: 'angle', label: 'الزاوية', type: 'select', value: '90', options: { '90': '90°', '180': '180°', '270': '270°' } },
          { name: 'pages', label: 'الصفحات (فارغ = الكل)', type: 'text', required: false, placeholder: 'الكل' },
        ],
      },
      images_pdf: {
        title: 'صور إلى PDF', icon: 'bi-images', engine: 'pdf', accept: IMAGE_EXT, multi: true,
        desc: 'حوّل صورة أو أكثر إلى ملف PDF.',
        fields: [{ name: 'size', label: 'حجم الصفحة', type: 'select', value: 'a4', options: { a4: 'A4', fit: 'بحجم الصورة' } }],
      },
      text_convert: {
        title: 'تحويل ملف نصي', icon: 'bi-filetype-json', engine: 'server', accept: TEXT_EXT,
        desc: 'CSV ⇄ JSON، نص إلى HTML، تنسيق JSON.',
        fields: [{ name: 'mode', label: 'التحويل', type: 'select', value: 'csv_json', options: {
          csv_json: 'CSV ← إلى JSON', json_csv: 'JSON ← إلى CSV', txt_html: 'نص ← إلى HTML', json_pretty: 'تنسيق JSON (مقروء)', json_minify: 'تصغير JSON',
        } }],
      },
      text_clean: {
        title: 'تنظيف النص', icon: 'bi-stars', engine: 'server', accept: TEXT_EXT,
        desc: 'حذف الفراغات الزائدة، الأسطر الفارغة، الترتيب والتكرار.',
        fields: [{ name: 'action', label: 'العملية', type: 'select', value: 'spaces', options: {
          spaces: 'حذف الفراغات الزائدة', empty_lines: 'حذف الأسطر الفارغة', unique: 'حذف الأسطر المكررة', sort: 'ترتيب الأسطر أبجدياً', upper: 'أحرف إنجليزية كبيرة', lower: 'أحرف إنجليزية صغيرة',
        } }],
      },
      text_replace: {
        title: 'بحث واستبدال', icon: 'bi-search', engine: 'server', accept: TEXT_EXT,
        desc: 'استبدل كلمة أو عبارة في كامل الملف.',
        fields: [
          { name: 'find', label: 'ابحث عن', type: 'text', maxlength: 500 },
          { name: 'replace', label: 'استبدل بـ', type: 'text', required: false, maxlength: 500 },
          { name: 'case_sensitive', label: 'مطابقة حالة الأحرف', type: 'checkbox', value: false },
        ],
      },
      text_encoding: {
        title: 'إصلاح الترميز', icon: 'bi-translate', engine: 'server', accept: TEXT_EXT,
        desc: 'حوّل الملفات ذات الحروف العربية المشوّهة إلى UTF-8.',
        fields: [{ name: 'from', label: 'الترميز الأصلي', type: 'select', value: 'auto', options: {
          auto: 'تلقائي', CP1256: 'Windows-1256 (عربي)', 'ISO-8859-6': 'ISO-8859-6 (عربي)', 'UTF-16': 'UTF-16', CP1252: 'Windows-1252 (لاتيني)',
        } }],
      },
      text_stats: {
        title: 'إحصائيات النص', icon: 'bi-bar-chart', engine: 'server', accept: TEXT_EXT,
        desc: 'عدد الكلمات والأحرف والأسطر وأكثر الكلمات تكراراً.', fields: [],
      },
      zip: {
        title: 'ضغط إلى ZIP', icon: 'bi-file-zip', engine: 'server', accept: [], multi: true,
        desc: 'اجمع أي ملفات في أرشيف ZIP واحد.',
        fields: [{ name: 'archive', label: 'اسم الأرشيف', type: 'text', required: false, maxlength: 80, placeholder: 'files' }],
      },
    },
  },
};

// تخزين مشترك في ذاكرة Edge مع مزامنة جلسة المستخدم في الكوكيز
const DB = { users: [], shares: [], operations: [] };

const e = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

function fmtBytes(b) {
  const u = ['بايت', 'KB', 'MB', 'GB'];
  let i = 0, n = Number(b) || 0;
  while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
  return `${n.toFixed(i ? 1 : 0)} ${u[i]}`;
}

function catOfMime(m = '') {
  if (m.startsWith('image/')) return 'image';
  if (m.startsWith('video/')) return 'video';
  if (m.startsWith('audio/')) return 'audio';
  if (m === 'application/pdf' || m.startsWith('text/') || m.includes('json') || m.includes('word') || m.includes('sheet')) return 'document';
  return 'other';
}
const catLabel = (c) => ({ image: 'صورة', video: 'فيديو', audio: 'صوت', document: 'مستند', other: 'ملف' }[c] || 'ملف');
const catIcon  = (c) => ({ image: 'bi-image', video: 'bi-camera-video', audio: 'bi-music-note-beamed', document: 'bi-file-earmark-text', other: 'bi-file-earmark' }[c] || 'bi-file-earmark');

async function sha256(str) {
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(str));
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

function parseCookies(req) {
  const out = {};
  (req.headers.get('Cookie') || '').split(';').forEach((p) => {
    const idx = p.indexOf('=');
    if (idx > 0) out[p.slice(0, idx).trim()] = decodeURIComponent(p.slice(idx + 1).trim());
  });
  return out;
}

function getSession(req) {
  const c = parseCookies(req);
  let state = { user: null, shares: [], ops: [], flash: [], csrf: c.ju_csrf || crypto.randomUUID().replace(/-/g, '') };
  if (c.ju_state) {
    try {
      const parsed = JSON.parse(decodeURIComponent(escape(atob(c.ju_state))));
      state = { ...state, ...parsed, csrf: state.csrf };
      // مزامنة مع ذاكرة الـ Worker
      if (state.user && !DB.users.find((u) => u.email === state.user.email)) DB.users.push(state.user);
      (state.shares || []).forEach((s) => { if (!DB.shares.find((x) => x.token === s.token)) DB.shares.push(s); });
    } catch {}
  }
  return state;
}

function makeHeaders(state, extra = {}) {
  const h = new Headers({ 'Content-Type': 'text/html; charset=utf-8', ...extra });
  const copy = { user: state.user, shares: (state.shares || []).slice(0, 25), ops: (state.ops || []).slice(0, 20), flash: state.flash || [] };
  const encoded = btoa(unescape(encodeURIComponent(JSON.stringify(copy))));
  h.append('Set-Cookie', `ju_csrf=${state.csrf}; Path=/; HttpOnly; SameSite=Lax`);
  h.append('Set-Cookie', `ju_state=${encoded}; Path=/; HttpOnly; SameSite=Lax; Max-Age=2592000`);
  return h;
}

function redirect(path, state) {
  return new Response(null, { status: 302, headers: makeHeaders(state, { Location: path }) });
}

function jsonRes(data, status = 200, state = null) {
  const h = state ? makeHeaders(state, { 'Content-Type': 'application/json; charset=utf-8' }) : new Headers({ 'Content-Type': 'application/json; charset=utf-8' });
  return new Response(JSON.stringify(data), { status, headers: h });
}

function renderField(f, prefix) {
  const id = e(`${prefix}-${f.name}`);
  const name = e(f.name);
  const label = e(f.label);
  const val = f.value ?? '';
  const req = (f.required ?? true) ? ' required' : '';
  let attrs = '';
  for (const a of ['min', 'max', 'step', 'placeholder', 'maxlength']) {
    if (f[a] !== undefined) attrs += ` ${a}="${e(f[a])}"`;
  }
  if (f.type === 'checkbox') {
    return `<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="${id}" name="${name}" value="1"${val ? ' checked' : ''}><label class="form-check-label" for="${id}">${label}</label></div>`;
  }
  if (f.type === 'select') {
    const opts = Object.entries(f.options).map(([k, t]) => `<option value="${e(k)}"${String(k) === String(val) ? ' selected' : ''}>${e(t)}</option>`).join('');
    return `<div class="mb-3"><label class="form-label" for="${id}">${label}</label><select class="form-select" id="${id}" name="${name}"${req}>${opts}</select></div>`;
  }
  if (f.type === 'range') {
    const suf = e(f.suffix || '');
    return `<div class="mb-3"><label class="form-label d-flex justify-content-between" for="${id}">${label} <output class="fw-bold" data-for="${id}">${e(val)}${suf}</output></label><input type="range" class="form-range" id="${id}" name="${name}" value="${e(val)}" data-suffix="${suf}"${attrs}></div>`;
  }
  const type = f.type === 'number' ? 'number' : 'text';
  let input = `<input type="${type}" class="form-control" id="${id}" name="${name}" value="${e(val)}"${attrs}${req}>`;
  if (f.media_time) {
    input = `<div class="input-group">${input}<button class="btn btn-outline-secondary" type="button" data-media-time="${id}" title="استخدم موضع التشغيل الحالي"><i class="bi bi-stopwatch"></i> الآن</button></div>`;
  }
  return `<div class="mb-3"><label class="form-label" for="${id}">${label}</label>${input}</div>`;
}

function shareBox(url) {
  const u = e(url);
  return `<div class="share-box"><div class="qr" data-qr="${u}"></div><div class="flex-grow-1 w-100"><label class="form-label small text-secondary">رابط المشاركة</label><div class="input-group mb-2" dir="ltr"><input type="text" class="form-control" value="${u}" readonly><button class="btn btn-accent" type="button" data-copy="${u}"><i class="bi bi-clipboard"></i> نسخ</button></div><div class="d-flex flex-wrap gap-2"><a class="btn btn-light btn-sm" href="${u}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-left"></i> فتح</a><button class="btn btn-light btn-sm" type="button" data-qr-download="${u}"><i class="bi bi-download"></i> تنزيل QR</button></div></div></div>`;
}

function layout(state, { title = APP_NAME, active = '', body = '', scripts = [] }) {
  const flashes = (state.flash || []).map((f) => `<div class="alert alert-${e(f.type)} alert-dismissible fade show">${e(f.message)}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`).join('');
  state.flash = [];
  const navLinks = Object.entries(CATEGORIES).map(([k, c]) => `<li class="nav-item"><a class="nav-link ${active === k ? 'active' : ''}" href="${e(c.page)}"><i class="bi ${e(c.icon)}"></i> ${e(c.title)}</a></li>`).join('');
  const userMenu = state.user
    ? `<div class="dropdown"><button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> ${e(state.user.name)}</button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-grid"></i> ملفاتي</a></li><li><a class="dropdown-item" href="account.php"><i class="bi bi-gear"></i> الحساب</a></li><li><hr class="dropdown-divider"></li><li><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="${e(state.csrf)}"><button class="dropdown-item text-danger"><i class="bi bi-box-arrow-left"></i> تسجيل الخروج</button></form></li></ul></div>`
    : `<a class="btn btn-light btn-sm ${active === 'login' ? 'active' : ''}" href="login.php">دخول</a><a class="btn btn-outline-dark btn-sm" href="register.php">حساب جديد</a>`;

  return `<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="${e(state.csrf)}">
  <title>${e(title)} · ${APP_NAME}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom bg-white sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-folder2-open text-accent"></i> ${APP_NAME}</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">${navLinks}</ul>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-accent btn-sm" href="share.php"><i class="bi bi-qr-code"></i> مشاركة ملف</a>
        ${userMenu}
      </div>
    </div>
  </div>
</nav>
<main class="container py-4 py-lg-5">${flashes}${body}</main>
<footer class="border-top py-4 mt-5 bg-white">
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-secondary">
    <div><i class="bi bi-folder2-open text-accent"></i> <strong>${APP_NAME}</strong> — كل أدوات ملفاتك في مكان واحد</div>
    <nav class="d-flex gap-3">
      ${Object.values(CATEGORIES).map((c) => `<a class="link-secondary text-decoration-none" href="${e(c.page)}">${e(c.title)}</a>`).join('')}
      <a class="link-secondary text-decoration-none" href="share.php">المشاركة</a>
    </nav>
    <div>&copy; ${new Date().getFullYear()}</div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/qrcode.js"></script>
<script src="assets/js/app.js"></script>
${scripts.map((s) => `<script src="${e(s)}"></script>`).join('')}
</body></html>`;
}

async function uploadToExternalHost(file) {
  // 1) x0.at
  try {
    const fd = new FormData();
    fd.append('file', file, file.name);
    const r = await fetch('https://x0.at', { method: 'POST', body: fd, headers: { 'User-Agent': 'curl/8.5.0' } });
    const txt = (await r.text()).trim();
    if (r.ok && /^https:\/\/x0\.at\/\S+$/.test(txt)) return txt;
  } catch {}

  // 2) uguu.se
  try {
    const fd = new FormData();
    fd.append('files[]', file, file.name);
    const r = await fetch('https://uguu.se/upload', { method: 'POST', body: fd });
    const j = await r.json();
    if (j?.files?.[0]?.url) return j.files[0].url;
  } catch {}

  // 3) tmpfiles.org
  try {
    const fd = new FormData();
    fd.append('file', file, file.name);
    const r = await fetch('https://tmpfiles.org/api/v1/upload', { method: 'POST', body: fd });
    const j = await r.json();
    if (j?.data?.url) return j.data.url.replace(/^https?:\/\/tmpfiles\.org\//, 'https://tmpfiles.org/dl/');
  } catch {}

  return null;
}

function handleTextTool(tool, text, base, ext, fd) {
  if (tool === 'text_convert') {
    const mode = fd.get('mode') || 'csv_json';
    if (mode === 'csv_json') {
      const lines = text.split(/\r?\n/).filter((l) => l.trim() !== '');
      if (lines.length < 2) throw new Error('ملف CSV يجب أن يحتوي على سطر عناوين وسطر بيانات على الأقل.');
      const headers = lines[0].split(',').map((h) => h.trim());
      const rows = lines.slice(1).map((l) => {
        const cols = l.split(',');
        return Object.fromEntries(headers.map((h, i) => [h, (cols[i] || '').trim()]));
      });
      return { data: JSON.stringify(rows, null, 2), name: `${base}.json`, mime: 'application/json', info: `${rows.length} صف` };
    }
    if (mode === 'json_csv') {
      const arr = JSON.parse(text);
      if (!Array.isArray(arr) || !arr.length) throw new Error('يجب أن يكون JSON مصفوفة كائنات.');
      const headers = [...new Set(arr.flatMap((o) => Object.keys(o || {})))];
      const csv = '\uFEFF' + [headers.join(','), ...arr.map((r) => headers.map((h) => JSON.stringify(r?.[h] ?? '')).join(','))].join('\n');
      return { data: csv, name: `${base}.csv`, mime: 'text/csv; charset=utf-8', info: `${arr.length} صف` };
    }
    if (mode === 'json_pretty' || mode === 'json_minify') {
      const obj = JSON.parse(text);
      return { data: JSON.stringify(obj, null, mode === 'json_pretty' ? 2 : 0), name: `${base}.json`, mime: 'application/json' };
    }
    if (mode === 'txt_html') {
      const body = text.trim().split(/(\r?\n){2,}/).filter((p) => p.trim()).map((p) => `<p>${e(p).replace(/\r?\n/g, '<br>')}</p>`).join('\n');
      return { data: `<!doctype html><html lang="ar" dir="auto"><head><meta charset="utf-8"><title>${e(base)}</title></head><body>${body}</body></html>`, name: `${base}.html`, mime: 'text/html; charset=utf-8' };
    }
  }
  if (tool === 'text_clean') {
    const action = fd.get('action') || 'spaces';
    const lines = text.split(/\r?\n/);
    let out = text;
    if (action === 'spaces') out = lines.map((l) => l.replace(/[ \t]+/g, ' ').trim()).join('\n');
    else if (action === 'empty_lines') out = lines.filter((l) => l.trim() !== '').join('\n');
    else if (action === 'unique') out = [...new Set(lines)].join('\n');
    else if (action === 'sort') out = [...lines].sort((a, b) => a.localeCompare(b, 'ar')).join('\n');
    else if (action === 'upper') out = text.toUpperCase();
    else if (action === 'lower') out = text.toLowerCase();
    return { data: out, name: `${base}-clean.txt`, mime: 'text/plain; charset=utf-8' };
  }
  if (tool === 'text_replace') {
    const find = fd.get('find') || '';
    const rep = fd.get('replace') || '';
    const cs = fd.get('case_sensitive') === '1';
    const rx = new RegExp(find.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), cs ? 'g' : 'gi');
    const matches = (text.match(rx) || []).length;
    return { data: text.replace(rx, rep), name: `${base}-replaced.${ext}`, mime: 'text/plain; charset=utf-8', info: `تم استبدال ${matches} موضع` };
  }
  if (tool === 'text_encoding') {
    return { data: text, name: `${base}-utf8.${ext}`, mime: 'text/plain; charset=utf-8', info: 'تم التحويل إلى UTF-8' };
  }
  if (tool === 'text_stats') {
    const words = text.match(/[\p{L}\p{N}']+/gu) || [];
    return {
      json: {
        stats: {
          'الكلمات': words.length,
          'الأحرف': [...text].length,
          'الأحرف بدون مسافات': [...text.replace(/\s+/gu, '')].length,
          'الأسطر': text === '' ? 0 : text.split(/\r?\n/).length,
          'وقت القراءة': `${Math.max(1, Math.ceil(words.length / 200))} دقيقة`,
        },
      },
    };
  }
  throw new Error('أداة غير معروفة.');
}

function renderToolPage(state, category) {
  const cat = CATEGORIES[category];
  const engines = [...new Set(Object.values(cat.tools).map((t) => t.engine))];
  const scripts = engines.includes('ffmpeg') ? ['assets/vendor/ffmpeg.js', 'assets/js/media.js'] : engines.includes('pdf') ? ['assets/vendor/pdf-lib.min.js', 'assets/js/pdf.js'] : [];
  const jsTools = Object.fromEntries(Object.entries(cat.tools).map(([k, t]) => [k, { title: t.title, desc: t.desc, engine: t.engine, accept: t.accept, multi: !!t.multi, cropUi: !!t.crop_ui }]));
  const chips = Object.entries(cat.tools).map(([k, t]) => `<button type="button" class="tool-chip" data-tool="${e(k)}" role="tab"><i class="bi ${e(t.icon)}"></i><span>${e(t.title)}</span></button>`).join('');
  const forms = Object.entries(cat.tools).map(([k, t]) => `<form class="tool-form d-none" id="form-${e(k)}" data-tool="${e(k)}"><input type="hidden" name="csrf_token" value="${e(state.csrf)}">${t.fields.map((f) => renderField(f, k)).join('')}<button type="submit" class="btn btn-accent w-100"><i class="bi bi-play-fill"></i> تنفيذ</button></form>`).join('');

  const body = `
    <header class="mb-4">
      <nav class="small mb-2"><a href="index.php" class="link-secondary text-decoration-none">الرئيسية</a> <span class="text-secondary">/ ${e(cat.title)}</span></nav>
      <h1 class="h3 fw-bold"><i class="bi ${e(cat.icon)} text-accent"></i> أدوات ${e(cat.title)}</h1>
      <p class="text-secondary mb-0">${e(cat.desc)}</p>
    </header>
    <div class="tool-grid mb-4" role="tablist">${chips}</div>
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="panel h-100">
          <label class="dropzone" id="dropzone">
            <input type="file" id="fileInput" hidden>
            <i class="bi bi-cloud-arrow-up display-6 text-accent"></i>
            <span class="fw-bold mt-2">اسحب الملف هنا أو اضغط للاختيار</span>
            <small class="text-secondary" id="acceptHint"></small>
          </label>
          <div id="preview" class="mt-3"></div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="panel">
          <h2 class="h5 fw-bold mb-1" id="toolTitle"></h2>
          <p class="small text-secondary" id="toolDesc"></p>
          ${forms}
          <div id="status" class="mt-3"></div>
          <p class="small text-secondary mt-3 mb-0" id="engineNote"></p>
        </div>
      </div>
    </div>
    <section id="result" class="panel mt-4 d-none" aria-live="polite"></section>
    <script>window.JU_PAGE = ${JSON.stringify({ category, tools: jsTools, maxUpload: MAX_UPLOAD_BYTES })};</script>`;
  return layout(state, { title: `أدوات ${cat.title}`, active: category, body, scripts });
}

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    let path = url.pathname.replace(/^\/+/, '');
    if (path === '') path = 'index.php';

    // تقديم الملفات الثابتة من مجلد assets
    if (path.startsWith('assets/') && env.ASSETS) {
      const assetUrl = new URL(request.url);
      assetUrl.pathname = '/' + path.slice('assets/'.length);
      return env.ASSETS.fetch(new Request(assetUrl, request));
    }

    const state = getSession(request);
    const isPost = request.method === 'POST';

    // صفحات أقسام الأدوات
    for (const [catKey, cat] of Object.entries(CATEGORIES)) {
      if (path === cat.page) {
        return new Response(renderToolPage(state, catKey), { headers: makeHeaders(state) });
      }
    }

    // معالجة الأدوات على الخادم
    if (path === 'api/process.php' && isPost) {
      try {
        const fd = await request.formData();
        const category = fd.get('category') || '';
        const tool = fd.get('tool') || '';
        const files = fd.getAll('files[]');
        if (!files.length) return jsonRes({ error: 'اختر ملفاً أولاً.' }, 422, state);
        state.ops.unshift({ category, tool, file_name: files[0].name, created_at: new Date().toISOString().slice(0, 19).replace('T', ' ') });
        DB.operations.push({ category });

        if (category === 'image' || tool === 'zip') {
          return jsonRes({ clientSide: true }, 200, state);
        }
        const file = files[0];
        const text = await file.text();
        const base = file.name.replace(/\.[^.]+$/, '');
        const ext = (file.name.split('.').pop() || 'txt').toLowerCase();
        const res = handleTextTool(tool, text, base, ext, fd);
        if (res.json) return jsonRes(res.json, 200, state);

        const h = makeHeaders(state, {
          'Content-Type': res.mime,
          'X-File-Name': encodeURIComponent(res.name),
          ...(res.info ? { 'X-Info': encodeURIComponent(res.info) } : {}),
        });
        return new Response(res.data, { headers: h });
      } catch (err) {
        return jsonRes({ error: err.message || 'حدث خطأ أثناء المعالجة.' }, 422, state);
      }
    }

    if (path === 'api/log.php' && isPost) {
      const fd = await request.formData();
      const category = fd.get('category') || '';
      const tool = fd.get('tool') || '';
      state.ops.unshift({ category, tool, file_name: fd.get('file_name') || '', created_at: new Date().toISOString().slice(0, 19).replace('T', ' ') });
      DB.operations.push({ category });
      return jsonRes({ ok: true }, 200, state);
    }

    // مشاركة ملف وتحويله إلى رابط خارجي و QR
    if (path === 'share.php') {
      if (isPost) {
        const fd = await request.formData();
        const ajax = fd.get('ajax') === '1';
        const file = fd.get('file');
        if (!file || !file.size) {
          return ajax ? jsonRes({ error: 'اختر ملفاً أولاً.' }, 422, state) : redirect('share.php', state);
        }
        const extUrl = await uploadToExternalHost(file);
        if (!extUrl) {
          const msg = 'تعذّر رفع الملف لخدمة الاستضافة السحابية، حاول مرة أخرى.';
          return ajax ? jsonRes({ error: msg }, 502, state) : redirect('share.php', state);
        }
        const token = crypto.randomUUID().replace(/-/g, '').slice(0, 16);
        const item = {
          id: Date.now(),
          token,
          title: (fd.get('title') || '').trim() || null,
          original_name: file.name,
          external_url: extUrl,
          mime: file.type || 'application/octet-stream',
          size: file.size,
          category: catOfMime(file.type || ''),
          downloads: 0,
          created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
          expires_at: null,
        };
        state.shares.unshift(item);
        DB.shares.unshift(item);
        state.ops.unshift({ category: 'share', tool: 'link', file_name: file.name, created_at: item.created_at });

        if (ajax) return jsonRes({ url: extUrl, token }, 200, state);
        state.flash.push({ type: 'success', message: 'تم رفع الملف وإنشاء رابط المشاركة العام ورمز QR بنجاح!' });
        return redirect(`f.php?t=${token}`, state);
      }

      const body = `
        <div class="text-center mb-4">
          <h1 class="h3 fw-bold"><i class="bi bi-qr-code text-accent"></i> حوّل أي ملف إلى رابط أو QR</h1>
          <p class="text-secondary">ارفع صورة، فيديو، مستند أو أي ملف — واحصل على رابط عام ورمز QR لمشاركته فوراً.</p>
        </div>
        <div class="row g-4 justify-content-center">
          <div class="col-lg-6">
            <div class="panel">
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="${e(state.csrf)}">
                <label class="dropzone mb-3" data-simple-dropzone>
                  <input type="file" name="file" hidden required>
                  <i class="bi bi-cloud-arrow-up display-6 text-accent"></i>
                  <span class="fw-bold mt-2" data-file-label>اسحب الملف هنا أو اضغط للاختيار</span>
                  <small class="text-secondary">أي نوع ملف — الحد الأقصى ${fmtBytes(MAX_UPLOAD_BYTES)}</small>
                </label>
                <div class="mb-3">
                  <label class="form-label" for="title">عنوان (اختياري)</label>
                  <input class="form-control" id="title" name="title" maxlength="150" placeholder="مثال: صور الرحلة">
                </div>
                <button class="btn btn-accent w-100 btn-lg"><i class="bi bi-link-45deg"></i> أنشئ الرابط و QR</button>
              </form>
            </div>
          </div>
        </div>`;
      return new Response(layout(state, { title: 'مشاركة ملف', active: 'share', body }), { headers: makeHeaders(state) });
    }

    // عرض الملف المشارك
    if (path === 'f.php') {
      const token = url.searchParams.get('t') || '';
      const share = state.shares.find((s) => s.token === token) || DB.shares.find((s) => s.token === token);
      if (!share) {
        const body = `<div class="panel text-center py-5 mx-auto" style="max-width:520px"><i class="bi bi-file-earmark-x display-4 text-secondary"></i><h1 class="h4 fw-bold mt-3">الملف غير موجود</h1><a class="btn btn-accent mt-2" href="share.php">شارك ملفاً جديداً</a></div>`;
        return new Response(layout(state, { title: 'ملف غير موجود', body }), { headers: makeHeaders(state) });
      }
      const pubUrl = share.external_url;
      const body = `
        <div class="row g-4 justify-content-center">
          <div class="col-lg-7">
            <div class="panel">
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="file-icon"><i class="bi ${e(catIcon(share.category))}"></i></div>
                <div class="min-w-0">
                  <h1 class="h5 fw-bold mb-0 text-truncate">${e(share.title || share.original_name)}</h1>
                  <div class="small text-secondary text-truncate">${e(share.original_name)}</div>
                </div>
              </div>
              <a class="btn btn-accent btn-lg w-100" href="${e(pubUrl)}" target="_blank" rel="noopener"><i class="bi bi-download"></i> فتح وتنزيل الملف (${fmtBytes(share.size)})</a>
              <dl class="meta-list mt-4 mb-0">
                <div><dt>النوع</dt><dd>${e(catLabel(share.category))}</dd></div>
                <div><dt>الحجم</dt><dd>${fmtBytes(share.size)}</dd></div>
                <div><dt>تاريخ الرفع</dt><dd dir="ltr">${e(share.created_at)}</dd></div>
              </dl>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="panel">
              <h2 class="h6 fw-bold mb-1"><i class="bi bi-share"></i> شارك هذا الملف</h2>
              <p class="small text-secondary mb-3"><i class="bi bi-globe2 text-accent"></i> رابط سحابي عام يعمل من أي جوال أو جهاز مباشرة</p>
              ${shareBox(pubUrl)}
            </div>
          </div>
        </div>`;
      return new Response(layout(state, { title: share.title || share.original_name, body }), { headers: makeHeaders(state) });
    }

    // التسجيل والدخول
    if (path === 'register.php') {
      if (isPost) {
        const fd = await request.formData();
        const name = (fd.get('name') || '').trim();
        const email = (fd.get('email') || '').trim().toLowerCase();
        const pass = fd.get('password') || '';
        if (name.length < 2 || !email.includes('@') || pass.length < 8) {
          state.flash.push({ type: 'danger', message: 'تأكد من إدخال اسم صحيح وبريد صالح وكلمة مرور من 8 أحرف على الأقل.' });
          return redirect('register.php', state);
        }
        const user = { id: Date.now(), name, email, passHash: await sha256(pass), created_at: new Date().toISOString().slice(0, 10) };
        DB.users.push(user);
        state.user = user;
        state.flash.push({ type: 'success', message: `أهلاً ${name}! تم إنشاء حسابك بنجاح.` });
        return redirect('dashboard.php', state);
      }
      const body = `<div class="auth-card panel mx-auto"><h1 class="h4 fw-bold mb-1">إنشاء حساب</h1><p class="text-secondary small mb-4">احفظ روابطك وأدِرها من لوحتك.</p><form method="post"><input type="hidden" name="csrf_token" value="${e(state.csrf)}"><div class="mb-3"><label class="form-label">الاسم</label><input class="form-control" name="name" required></div><div class="mb-3"><label class="form-label">البريد الإلكتروني</label><input class="form-control" type="email" name="email" required></div><div class="mb-3"><label class="form-label">كلمة المرور (8 أحرف على الأقل)</label><input class="form-control" type="password" name="password" required></div><div class="mb-3"><label class="form-label">تأكيد كلمة المرور</label><input class="form-control" type="password" name="password_confirm" required></div><button class="btn btn-accent w-100">إنشاء الحساب</button></form><p class="small text-center mt-3 mb-0">لديك حساب؟ <a href="login.php">سجّل الدخول</a></p></div>`;
      return new Response(layout(state, { title: 'حساب جديد', body }), { headers: makeHeaders(state) });
    }

    if (path === 'login.php') {
      if (isPost) {
        const fd = await request.formData();
        const email = (fd.get('email') || '').trim().toLowerCase();
        const passHash = await sha256(fd.get('password') || '');
        const found = DB.users.find((u) => u.email === email && u.passHash === passHash);
        if (!found) {
          state.flash.push({ type: 'danger', message: 'البريد الإلكتروني أو كلمة المرور غير صحيحة.' });
          return redirect('login.php', state);
        }
        state.user = found;
        state.flash.push({ type: 'success', message: `مرحباً بعودتك، ${found.name}!` });
        return redirect('dashboard.php', state);
      }
      const body = `<div class="auth-card panel mx-auto"><h1 class="h4 fw-bold mb-1">تسجيل الدخول</h1><p class="text-secondary small mb-4">ادخل لإدارة ملفاتك وروابطك.</p><form method="post"><input type="hidden" name="csrf_token" value="${e(state.csrf)}"><div class="mb-3"><label class="form-label">البريد الإلكتروني</label><input class="form-control" type="email" name="email" required></div><div class="mb-3"><label class="form-label">كلمة المرور</label><input class="form-control" type="password" name="password" required></div><button class="btn btn-accent w-100">دخول</button></form><p class="small text-center mt-3 mb-0">ليس لديك حساب؟ <a href="register.php">أنشئ حساباً</a></p></div>`;
      return new Response(layout(state, { title: 'تسجيل الدخول', active: 'login', body }), { headers: makeHeaders(state) });
    }

    if (path === 'logout.php') {
      state.user = null;
      state.flash.push({ type: 'success', message: 'تم تسجيل الخروج.' });
      return redirect('index.php', state);
    }

    if (path === 'dashboard.php') {
      if (!state.user) return redirect('login.php', state);
      if (isPost) {
        const fd = await request.formData();
        if (fd.get('action') === 'delete') {
          const id = Number(fd.get('id'));
          state.shares = state.shares.filter((s) => s.id !== id);
          state.flash.push({ type: 'success', message: 'تم حذف الرابط.' });
        } else if (fd.get('action') === 'clear_history') {
          state.ops = [];
          state.flash.push({ type: 'success', message: 'تم مسح السجل.' });
        }
        return redirect('dashboard.php', state);
      }
      const rows = state.shares.map((s) => `<tr><td><a class="fw-bold text-decoration-none" href="f.php?t=${e(s.token)}">${e(s.title || s.original_name)}</a></td><td>${fmtBytes(s.size)}</td><td class="text-end"><button class="btn btn-light btn-sm" type="button" data-copy="${e(s.external_url)}"><i class="bi bi-clipboard"></i></button> <a class="btn btn-light btn-sm" href="f.php?t=${e(s.token)}"><i class="bi bi-qr-code"></i></a> <form method="post" class="d-inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${s.id}"><button class="btn btn-light btn-sm text-danger"><i class="bi bi-trash"></i></button></form></td></tr>`).join('');
      const body = `
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div><h1 class="h3 fw-bold mb-0">ملفاتي</h1><p class="text-secondary mb-0">أهلاً ${e(state.user.name)}</p></div>
          <a class="btn btn-accent" href="share.php"><i class="bi bi-plus-lg"></i> مشاركة ملف</a>
        </div>
        <div class="panel mb-4">${rows ? `<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>الملف</th><th>الحجم</th><th class="text-end">إجراءات</th></tr></thead><tbody>${rows}</tbody></table></div>` : '<p class="text-center text-secondary py-4 mb-0">لا توجد ملفات مشاركة بعد.</p>'}</div>`;
      return new Response(layout(state, { title: 'ملفاتي', body }), { headers: makeHeaders(state) });
    }

    if (path === 'account.php') {
      if (!state.user) return redirect('login.php', state);
      if (isPost) {
        const fd = await request.formData();
        if (fd.get('action') === 'profile') {
          state.user.name = (fd.get('name') || state.user.name).trim();
          state.flash.push({ type: 'success', message: 'تم تحديث الاسم.' });
        }
        return redirect('account.php', state);
      }
      const body = `<div class="mx-auto" style="max-width:620px"><h1 class="h3 fw-bold mb-1">إعدادات الحساب</h1><p class="text-secondary mb-4">${e(state.user.email)}</p><div class="panel mb-4"><h2 class="h6 fw-bold mb-3">الملف الشخصي</h2><form method="post" class="d-flex gap-2"><input type="hidden" name="action" value="profile"><input class="form-control" name="name" value="${e(state.user.name)}" required><button class="btn btn-accent text-nowrap">حفظ الاسم</button></form></div></div>`;
      return new Response(layout(state, { title: 'الحساب', body }), { headers: makeHeaders(state) });
    }

    // الصفحة الرئيسية
    const cards = Object.entries(CATEGORIES).map(([k, c]) => `
      <div class="col-md-6 col-lg-3">
        <a class="cat-card panel h-100 d-flex flex-column text-decoration-none text-reset" href="${e(c.page)}">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="cat-icon"><i class="bi ${e(c.icon)}"></i></div>
            <span class="badge text-bg-light">${Object.keys(c.tools).length} أدوات</span>
          </div>
          <h3 class="h5 fw-bold mb-1">${e(c.title)}</h3>
          <p class="small text-secondary mb-3 flex-grow-1">${e(c.desc)}</p>
          <div class="d-flex flex-wrap gap-1 mb-3">${Object.values(c.tools).map((t) => `<span class="mini-tag">${e(t.title)}</span>`).join('')}</div>
          <div class="d-flex justify-content-between align-items-center small fw-bold text-accent mt-auto pt-2 border-top">
            <span>فتح أدوات ${e(c.title)}</span><i class="bi bi-arrow-left"></i>
          </div>
        </a>
      </div>`).join('');

    const homeBody = `
      <section class="hero text-center py-4 py-lg-5 mb-4">
        <span class="badge rounded-pill bg-accent-soft text-accent px-3 py-2 mb-3"><i class="bi bi-magic"></i> بسيط · سريع · آمن</span>
        <h1 class="display-6 fw-bold mb-3">اختر نوع الملف وابدأ التعديل فوراً</h1>
        <p class="lead text-secondary mx-auto mb-4" style="max-width:640px">منصة متخصصة لتعديل الملفات، تحويل الصيغ، وتصغير الحجم — مع إمكانية تحويل أي ملف إلى رابط مباشر أو رمز QR للمشاركة السريعة.</p>
        <div class="d-flex justify-content-center flex-wrap gap-2">
          <a href="share.php" class="btn btn-accent btn-lg px-4"><i class="bi bi-qr-code"></i> حوّل ملفاً إلى رابط أو QR</a>
          ${state.user ? '<a href="dashboard.php" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-grid"></i> لوحة ملفاتي</a>' : '<a href="register.php" class="btn btn-outline-dark btn-lg px-4">حساب مجاني لحفظ روابطك</a>'}
        </div>
      </section>
      <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-bold mb-0">ماذا تريد أن تعدّل اليوم؟</h2>
          <span class="small text-secondary">تظهر لك الخيارات المتاحة لكل نوع ملف تحديداً</span>
        </div>
        <div class="row g-3">${cards}</div>
      </section>
      <section class="panel mb-4">
        <div class="row g-4 align-items-center">
          <div class="col-lg-7">
            <h2 class="h5 fw-bold mb-2"><i class="bi bi-qr-code-scan text-accent"></i> مشاركة أي ملف برابط أو رمز QR</h2>
            <p class="text-secondary mb-3">سواء عدّلت ملفك هنا أو كان لديك ملف جاهز على جهازك، يمكنك تحويله خلال ثوانٍ إلى رابط عام ورمز QR قابل للمسح بالجوال.</p>
            <a href="share.php" class="btn btn-accent"><i class="bi bi-upload"></i> ارفع ملفاً للمشاركة</a>
          </div>
          <div class="col-lg-5">
            <div class="row g-2 text-center">
              <div class="col-6"><div class="p-3 rounded-3 bg-light"><div class="h4 fw-bold mb-0 text-accent">${DB.operations.length + state.ops.length}</div><div class="small text-secondary">عملية معالجة منفّذة</div></div></div>
              <div class="col-6"><div class="p-3 rounded-3 bg-light"><div class="h4 fw-bold mb-0 text-accent">${DB.shares.length || state.shares.length}</div><div class="small text-secondary">رابط مشاركة نشط</div></div></div>
            </div>
          </div>
        </div>
      </section>`;

    return new Response(layout(state, { title: 'كل أدوات ملفاتك في مكان واحد', active: 'home', body: homeBody }), { headers: makeHeaders(state) });
  },
};

