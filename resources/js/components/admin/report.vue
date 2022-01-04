<template>
  <div>
    <v-row>
      <v-col cols="3">
        <div>
          <v-card class="pa-5 w-100">
            Укажите пользователя
            <v-card-text class="scroll-y">
              <v-list>
                <v-expansion-panels>
                  <v-expansion-panel v-for="(item, i) in group" :key="i">
                    <v-expansion-panel-header>
                      {{ item.fio }}
                    </v-expansion-panel-header>
                    <v-expansion-panel-content>
                      <v-row
                        v-for="user in users.filter(function (i) {
                          return i.group_id == item.group_id;
                        })"
                        :key="user.id"
                      >
                        <v-btn
                          class="ml-3"
                          small
                          :color="usercolor(user)"
                          @click="getLids(user.id)"
                          :value="user.id"
                          :disabled="disableuser == user.id"
                          >{{ user.fio }}</v-btn
                        >
                      </v-row>
                    </v-expansion-panel-content>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-list>
            </v-card-text>
          </v-card>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script>
export default {
  data: () => ({
    users: [],
    userid: 0,
  }),
  mounted: function () {
    this.getUsers();
  },
  methods: {
    getUsers() {
      let self = this;
      axios
        .get("/api/getusers")
        .then((res) => {
          self.users = res.data.map(
            ({ name, id, role_id, fio, hmlids, group_id }) => ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
            })
          );
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>
