<template>
  <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-auto">
    <div
        v-for="(item, i) in permissions"
        :key="i"
        class="flex items-center justify-between border-t py-2"
      >
        <div>
          <p class="font-medium">{{ item.name }}</p>
          <p class="text-xs text-gray-500">
            {{ item.description }}
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button :class="['w-7 h-7 flex items-center justify-center rounded border border-gray-600',
            item.rpermission_id <= 0 ?'bg-red-600 text-white border-red-500': 'hover:border-gray-400 hover:text-white text-gray-400'
          ]"  @click="onSelectPermission(item, false)">✖</button>
          <button :class="['w-7 h-7 flex items-center justify-center rounded border border-gray-600',
            item.rpermission_id > 0 ?'bg-green-600 text-white border-green-500': 'hover:border-gray-400 hover:text-white text-gray-400'
          ]" @click="onSelectPermission(item, true)">✔</button>
          
        </div>
      </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: "PermissionsView",
  props:{
      roleId:{
      type: Number,
      required: true
    }
},
  data() {
    return {
      permissions: [],
      options: [
        { value: "deny", icon: "✖", activeClass: "bg-red-600 text-white border-red-500" },
        { value: "allow", icon: "✔", activeClass: "bg-green-600 text-white border-green-500" },
      ],
      permissionSelected: []
    };
  },
  created() {
      this.getPermissions();
  },
  methods: {
    setPermission(section, label, value) {
      const sec = this.permissions.find((p) => p.section === section);
      const item = sec.items.find((i) => i.label === label);
      item.value = value;
    },
    getPermissions(){
        axios.get(this.route('permission.roles', { roleId: this.roleId }))
        .then((response)=>{
          const data = response.data;
          if(!data.error){
              this.permissions = data.data;
          }
        });
    },
    onSelectPermission(item, isAllowed){
      const itemsSelected = this.permissionSelected;
      const index = itemsSelected.findIndex(x => x.permission_id === item.id);

      if(index === -1){
          itemsSelected.push({ permission_id: item.id, role_id: this.roleId, selected: isAllowed});
       } else{
          itemsSelected[index].selected = isAllowed;
       }
       item.rpermission_id = isAllowed ? item.id : 0;

       this.$emit("onSelectPermission", this.permissionSelected)
    },

  },
};
</script>
