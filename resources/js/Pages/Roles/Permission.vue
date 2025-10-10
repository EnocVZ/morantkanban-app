<template>
  <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-auto">
    <div v-for="(perm, index) in permissions" :key="index" class="mb-5">
      <h3 v-if="perm?.section" class="text-sm font-semibold uppercase text-gray-400 mb-2">
        {{ perm.section }}
      </h3>

      <div
        v-for="(item, i) in perm.items"
        :key="i"
        class="flex items-center justify-between border-t py-2"
      >
        <div>
          <p class="font-medium">{{ item.label }}</p>
          <p class="text-xs text-gray-500">
            {{ item.description }}
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            v-for="option in options"
            :key="option.value"
            :class="[
              'w-7 h-7 flex items-center justify-center rounded border border-gray-600',
              item.value === option.value
                ? option.activeClass
                : 'hover:border-gray-400 hover:text-white text-gray-400',
            ]"
            @click="setPermission(perm.section, item.label, option.value)"
          >
            {{ option.icon }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "PermissionsView",
  data() {
    return {
      permissions: [
        {
          section: "General Permissions",
          items: [
            {
              label: "Create Instant Invite",
              description:
                "Members with this permission can create invite links to this channel.",
              value: null,
            },
            {
              label: "Manage Channel",
              description:
                "Members with this permission can change the channel's name or delete it.",
              value: null,
            },
            {
              label: "Manage Permissions",
              description:
                "Members with this permission can change this channel's permissions.",
              value: null,
            },
          ],
        },
        {
          items: [
            {
              label: "Read Messages",
              description:
                "Members with this permission can read messages in this channel.",
              value: null,
            },
            {
              label: "Send Messages",
              description:
                "Members with this permission can send messages in this channel.",
              value: null,
            },
          ],
        },
      ],
      options: [
        { value: "deny", icon: "✖", activeClass: "bg-red-600 text-white border-red-500" },
        { value: "allow", icon: "✔", activeClass: "bg-green-600 text-white border-green-500" },
      ],
    };
  },
  methods: {
    setPermission(section, label, value) {
      const sec = this.permissions.find((p) => p.section === section);
      const item = sec.items.find((i) => i.label === label);
      item.value = value;
    },
  },
};
</script>
