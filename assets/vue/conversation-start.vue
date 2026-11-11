<script>
import SearchBox from '@wexample/symfony-search/vue/search/search-box.vue';

// Someone to write to, found by name or address: picked, their conversation
// with the reader opens — the one held, or a new one — where the screen is
// held, as a link of it would (`withUrl`, `__user__` standing for their id).
export default {
  extends: SearchBox,

  template: '#vue-template-wexample-symfony-conversation-ds-bundle-vue-conversation-start',

  props: {
    withUrl: {
      type: String,
      required: true
    },
    type: {
      type: String,
      default: 'user'
    },
    context: {
      type: String,
      default: 'form_field'
    },
    linkResults: {
      type: Boolean,
      default: false
    }
  },

  methods: {
    onSelect(payload) {
      const id = this.readResultValue(payload?.result ?? payload, 'reference');

      if (!id) {
        return;
      }

      this.reset();

      // Followed as a link of the screen is, so that it opens where the
      // screen is held — the dock's panel — and not over the page.
      const link = document.createElement('a');
      link.href = this.withUrl.replace('__user__', encodeURIComponent(id));
      link.hidden = true;
      this.$el.appendChild(link);
      link.click();
      link.remove();
    },

    // Through the entity's accessor: the generated class keeps its values in
    // its data.
    readResultValue(result, name) {
      const value = typeof result?.getDataValue === 'function'
        ? result.getDataValue(name)
        : result?.[name];

      return typeof value === 'string' ? value : '';
    }
  }
};
</script>
