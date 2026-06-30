document.addEventListener("DOMContentLoaded", function() {
  const tocRoot = document.querySelector('.toc-list-v2');
  if (!tocRoot) return;
  // h2, h3만 타겟(추후 추가하고 싶으면 selector만 바꾸면 됨)
  const headings = document.querySelectorAll('.post-content h2, .post-content h3');
  if (!headings.length) return;

  let tocHTML = '';
  headings.forEach((h, idx) => {
    if (!h.id) h.id = 'toc-h' + idx;
    tocHTML += `<a class="toc-link-v2" href="#${h.id}">${h.innerText}</a>`;
  });
  tocRoot.innerHTML = tocHTML;

  // 클릭 시 스무스 스크롤 이동
  tocRoot.querySelectorAll('.toc-link-v2').forEach(link => {
    link.addEventListener('click', function(e){
      e.preventDefault();
      const target = document.getElementById(this.getAttribute('href').substring(1));
      if (target) {
        const yOffset = -90;
        const y = target.getBoundingClientRect().top + window.pageYOffset + yOffset;
        window.scrollTo({ top: y, behavior: 'smooth' });
      }
    });
  });

  // 스크롤시 active 표시 (passive + RAF + throttle으로 스크롤 부드럽게)
  const tocLinks = Array.from(tocRoot.querySelectorAll('.toc-link-v2'));
  let tocRaf = null;
  let tocLastRun = 0;
  const TOC_THROTTLE_MS = 50;

  function setActiveTocV2() {
    tocRaf = null;
    const scrollPos = window.scrollY + 120;
    let activeIdx = -1;
    for (let i = 0; i < headings.length; i++) {
      const h = headings[i];
      const next = headings[i + 1];
      if (scrollPos >= h.offsetTop && (!next || scrollPos < next.offsetTop)) {
        activeIdx = i;
        break;
      }
    }
    tocLinks.forEach((l, i) => l.classList.toggle('active', i === activeIdx));
  }

  window.addEventListener('scroll', function() {
    if (tocRaf) return;
    tocRaf = requestAnimationFrame(function() {
      const now = typeof performance !== 'undefined' ? performance.now() : Date.now();
      if (now - tocLastRun < TOC_THROTTLE_MS) {
        tocRaf = null;
        return;
      }
      tocLastRun = now;
      setActiveTocV2();
    });
  }, { passive: true });
});
