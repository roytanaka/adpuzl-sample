<template>
  <nav class="footer-links">
    <ul class="footer-links__list">
      <li class="footer-links__item" v-for="link in links" :key="link.label">
        <a class="footer-links__link" :href="link.href">{{ link.label }}</a>
      </li>
    </ul>
  </nav>
</template>

<script>
export default {
  data: () => ({
    links: [
      {
        label: "AdPuzl.com",
        href: "https://adpuzl.com",
      },
      {
        label: "AdPuzl Blog",
        href: "https://adpuzl.com/blog",
      },
      {
        label: "Facebook Group",
        href: "https://www.facebook.com/groups/socialadsforbeginners",
      },
      {
        label: "Privacy Policy",
        href: "https://adpuzl.com/privacy-policy/",
      },
      {
        label: "Contact Us",
        href: "https://adpuzl.com/contact",
      },
    ],
  }),
};
</script>

<style scoped>
.footer-links {
  color: rgba(255, 255, 255, 0.8);
}
.footer-links__list {
  margin: 0;
  padding: 0;
  display: flex;
  flex-flow: row wrap;
  justify-content: center;
  list-style: none;
}
.footer-links__list a {
  color: inherit;
}
.footer-links__item:not(:first-child)::before {
  content: "•";
  margin: 0.5em;
}
</style>
