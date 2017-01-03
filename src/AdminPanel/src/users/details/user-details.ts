import {RoutableComponentActivate} from "aurelia-router";
import {inject} from "aurelia-dependency-injection";
import {UserRepository} from "../user-repository";
import {User} from "../user";
import {CurrentUserFetcher} from "../current/current-user-fetcher";
import {computedFrom} from "aurelia-binding";

@inject(UserRepository, CurrentUserFetcher.CURRENT_USER_KEY)
export class UserDetails implements RoutableComponentActivate {
  user: User;

  constructor(private userRepository: UserRepository, private currentUser: User) {
  }

  activate(params: any): void {
    if (params.id == this.currentUser.id) {
      this.user = this.currentUser;
    } else {
      this.userRepository.get(params.id).then(user => this.user = user);
    }
  }

  @computedFrom('user')
  get isCurrentUser() {
    return this.user == this.currentUser;
  }
}