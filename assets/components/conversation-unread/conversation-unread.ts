import Component from '@wexample/symfony-loader/js/Class/Component';
import LiveUpdatesService from '@wexample/symfony-loader/js/Services/LiveUpdatesService';
import { toolDockCountApply } from '@wexample/symfony-design-system/js/Helper/ToolDockHelper';

// What the server says on a person's topic as the lines waiting for them
// change (ConversationUnreadService): `{ unread }`, all conversations together.
const EVENT_UNREAD = 'conversation-unread';

export default class extends Component {
  protected async activateListeners(): Promise<void> {
    await super.activateListeners();

    const { user, tool } = this.el.dataset;

    if (!user || !tool) {
      return;
    }

    try {
      await this.app.getServiceOrFail(LiveUpdatesService).connectToEntity({
        entityName: 'user',
        id: user,
        owner: this,
        onMessage: (connection, payload) => {
          if (payload?.event === EVENT_UNREAD) {
            toolDockCountApply(tool, Number(payload.data?.unread) || 0);
          }
        },
      });
    } catch {
      // Not open to subscription here: the count stays what the page said.
    }
  }

  protected async deactivateListeners(): Promise<void> {
    this.app.getServiceOrFail(LiveUpdatesService).disconnectOwner(this);

    await super.deactivateListeners();
  }
}
